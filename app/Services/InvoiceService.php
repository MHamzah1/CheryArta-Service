<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\InvalidInvoiceTransitionException;
use App\Exceptions\InvoiceUnavailableException;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Support\InvoiceNumber;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * Seluruh aturan invoice (docs/05 §5.6).
 *
 * Lima hal yang HANYA boleh terjadi di sini, tidak di controller dan tidak di
 * React: perhitungan subtotal & total, penerbitan nomor, penetapan status,
 * pemeriksaan "satu invoice aktif per booking", dan penulisan `issued_at` /
 * `paid_at`. Seluruhnya ditentukan server — menerima salah satunya dari request
 * adalah larangan mutlak (.claude/rules/50).
 *
 * Kelas ini tidak menyentuh `request()`, `session()`, maupun `auth()`; pelakunya
 * selalu datang sebagai parameter agar bisa diuji (.claude/rules/10).
 */
class InvoiceService
{
    /**
     * Invoice draft dari satu booking yang sudah selesai.
     *
     * Dipanggil dua tempat: `BookingService::changeStatus()` saat status
     * berpindah ke `completed`, dan tombol "Buat Invoice" untuk booking yang
     * selesai sebelum Tahap 11 ada atau yang invoicenya baru saja di-void
     * (keputusan grill Q3).
     *
     * Baris BOOKING-nya dikunci, bukan baris invoice: yang belum ada tidak bisa
     * dikunci, dan sejak keputusan grill Q1 mencabut kolom unik pada
     * `booking_id`, database tidak lagi menangkap dua permintaan bersamaan.
     * Pola yang sama dengan perhitungan kuota slot di F1.4.
     */
    public function createDraftFor(Booking $booking, User $actor): Invoice
    {
        return DB::transaction(function () use ($booking, $actor): Invoice {
            /** @var Booking $terkini */
            $terkini = Booking::query()->lockForUpdate()->findOrFail($booking->getKey());

            if ($terkini->status !== BookingStatus::Completed) {
                throw InvoiceUnavailableException::bookingNotCompleted($terkini->status);
            }

            $aktif = $terkini->invoices()->active()->first();

            if ($aktif !== null) {
                throw InvoiceUnavailableException::alreadyExists($aktif->invoice_number);
            }

            $invoice = new Invoice;
            $invoice->forceFill([
                'booking_id' => $terkini->getKey(),
                'status' => InvoiceStatus::Draft,
                'created_by' => $actor->getKey(),
                'subtotal' => 0,
                'discount' => 0,
                'tax' => 0,
                'total' => 0,
            ])->save();

            // Satu item jasa dari paketnya. Paket ber-`is_free` tetap
            // menghasilkan baris berharga 0, bukan invoice tanpa item
            // (keputusan grill Q4): servis garansi tetap butuh bukti bahwa ia
            // dikerjakan, dan sparepart di luar cakupan garansi tetap ditagih.
            $paket = $terkini->servicePackage;

            $this->tambahBaris($invoice, InvoiceItemType::Jasa, $paket->name, 1, (float) $paket->price, 0);

            return $this->hitungUlang($invoice);
        });
    }

    /**
     * Menyimpan suntingan draft: item, diskon, pajak, catatan.
     *
     * Seluruh item ditulis ulang, bukan ditambal satu per satu. Bentuk ini
     * membuat penghapusan baris tidak butuh rute tersendiri, dan yang lebih
     * penting: tidak ada jalan bagi request untuk menyentuh item milik invoice
     * lain lewat id yang ditebak.
     *
     * @param  array{items: list<array{type: string, description: string, qty: numeric-string|float|int, unit_price: numeric-string|float|int}>, discount?: numeric-string|float|int|null, tax?: numeric-string|float|int|null, notes?: string|null}  $data
     */
    public function updateDraft(Invoice $invoice, array $data): Invoice
    {
        return DB::transaction(function () use ($invoice, $data): Invoice {
            /** @var Invoice $terkini */
            $terkini = Invoice::query()->lockForUpdate()->findOrFail($invoice->getKey());

            $this->pastikanMasihDraft($terkini);

            $terkini->items()->delete();

            foreach ($data['items'] as $urutan => $baris) {
                $this->tambahBaris(
                    $terkini,
                    InvoiceItemType::from($baris['type']),
                    $baris['description'],
                    (float) $baris['qty'],
                    (float) $baris['unit_price'],
                    $urutan,
                );
            }

            $terkini->forceFill([
                'discount' => $data['discount'] ?? 0,
                'tax' => $data['tax'] ?? 0,
                'notes' => $data['notes'] ?? null,
            ]);

            return $this->hitungUlang($terkini);
        });
    }

    /**
     * Menerbitkan invoice: status `issued` + nomor + `issued_at`.
     *
     * Nomor terbit DI SINI, bukan saat draft dibuat — draft yang tidak pernah
     * diterbitkan tidak boleh menghabiskan satu nomor pun, dan nomor yang
     * berlubang membuat orang mengira ada tagihan yang hilang.
     */
    public function issue(Invoice $invoice, User $actor): Invoice
    {
        return $this->transaksiNomorUnik(function () use ($invoice, $actor): Invoice {
            /** @var Invoice $terkini */
            $terkini = Invoice::query()->lockForUpdate()->findOrFail($invoice->getKey());

            $this->pastikanTransisi($terkini, InvoiceStatus::Issued);

            // Dihitung ULANG sebelum terbit, bukan dipercaya dari kolom yang
            // tersimpan: itu satu-satunya titik di mana angka final dikunci.
            $this->hitungUlang($terkini);

            if ($terkini->items()->count() === 0) {
                throw InvalidInvoiceTransitionException::needsItem();
            }

            $sekarang = now(Config::string('booking.timezone'));

            $terkini->forceFill([
                'status' => InvoiceStatus::Issued,
                'invoice_number' => InvoiceNumber::next($sekarang),
                'issued_at' => $sekarang,
                'created_by' => $terkini->created_by ?? $actor->getKey(),
            ])->save();

            return $terkini;
        });
    }

    /** Mencatat pembayaran. `payment_method` pencatatan saja — tanpa gateway. */
    public function markPaid(Invoice $invoice, PaymentMethod $paymentMethod): Invoice
    {
        return DB::transaction(function () use ($invoice, $paymentMethod): Invoice {
            /** @var Invoice $terkini */
            $terkini = Invoice::query()->lockForUpdate()->findOrFail($invoice->getKey());

            $this->pastikanTransisi($terkini, InvoiceStatus::Paid);

            $terkini->forceFill([
                'status' => InvoiceStatus::Paid,
                'paid_at' => now(Config::string('booking.timezone')),
                'payment_method' => $paymentMethod->value,
            ])->save();

            return $terkini;
        });
    }

    /**
     * Membatalkan invoice — Super Admin saja, ditegakkan InvoicePolicy.
     *
     * `paid_at` dan `payment_method` SENGAJA tidak dikosongkan (keputusan grill
     * Q2): keduanya jejak bahwa uangnya pernah tercatat masuk, dan menghapusnya
     * membuat log audit berbohong tentang apa yang sempat terjadi.
     */
    public function void(Invoice $invoice, string $reason): Invoice
    {
        return DB::transaction(function () use ($invoice, $reason): Invoice {
            /** @var Invoice $terkini */
            $terkini = Invoice::query()->lockForUpdate()->findOrFail($invoice->getKey());

            $this->pastikanTransisi($terkini, InvoiceStatus::Void);

            $terkini->forceFill([
                'status' => InvoiceStatus::Void,
                'void_reason' => $reason,
            ])->save();

            return $terkini;
        });
    }

    /**
     * `subtotal = Σ(qty × unit_price)` lalu `total = subtotal − discount + tax`
     * (docs/05 §5.6).
     *
     * Satu-satunya tempat rumus ini ditulis. Pratinjau di React memakai rumus
     * yang sama, tetapi hasilnya TIDAK pernah disimpan — menghitung total di
     * frontend adalah temuan invoice sistem lama (.claude/rules/20).
     *
     * Diskon dijepit pada rentang yang masuk akal sebagai jaring terakhir:
     * batas `discount ≤ subtotal` sudah ditegakkan Form Request, tetapi jalur
     * ini juga dilewati `createDraftFor` yang tidak melewati validasi apa pun,
     * dan invoice bertotal negatif bukan invoice.
     */
    private function hitungUlang(Invoice $invoice): Invoice
    {
        $subtotal = 0.0;

        foreach ($invoice->items()->get() as $item) {
            $barisSubtotal = round((float) $item->qty * (float) $item->unit_price, 2);

            $item->forceFill(['subtotal' => $barisSubtotal])->save();

            $subtotal += $barisSubtotal;
        }

        $subtotal = round($subtotal, 2);
        $discount = min(max((float) $invoice->discount, 0.0), $subtotal);
        $tax = max((float) $invoice->tax, 0.0);

        $invoice->forceFill([
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'total' => round($subtotal - $discount + $tax, 2),
        ])->save();

        return $invoice->load('items');
    }

    /**
     * Menyisipkan satu baris beserta subtotalnya.
     *
     * `subtotal` dipasang lewat `forceFill` karena kolom itu SENGAJA tidak
     * fillable: ia hasil hitungan server, dan `create()` biasa akan
     * meninggalkannya NULL. Menaruh `default(0)` di migration akan membuat
     * baris yang gagal dihitung tersimpan diam-diam sebagai nol rupiah —
     * kegagalan yang tidak berbunyi apa-apa sampai ada yang membaca invoicenya.
     */
    private function tambahBaris(
        Invoice $invoice,
        InvoiceItemType $type,
        string $description,
        float $qty,
        float $unitPrice,
        int $sortOrder,
    ): void {
        $baris = new InvoiceItem;

        $baris->forceFill([
            'invoice_id' => $invoice->getKey(),
            'type' => $type,
            'description' => $description,
            'qty' => $qty,
            'unit_price' => $unitPrice,
            'subtotal' => round($qty * $unitPrice, 2),
            'sort_order' => $sortOrder,
        ])->save();
    }

    private function pastikanMasihDraft(Invoice $invoice): void
    {
        if (! $invoice->status->isEditable()) {
            throw InvalidInvoiceTransitionException::notEditable($invoice->status);
        }
    }

    private function pastikanTransisi(Invoice $invoice, InvoiceStatus $target): void
    {
        if (! $invoice->status->canTransitionTo($target)) {
            throw InvalidInvoiceTransitionException::between($invoice->status, $target);
        }
    }

    /**
     * Menjalankan transaksi, mengulangnya bila nomor invoicenya keburu dipakai.
     *
     * Baris yang belum ada tidak bisa dikunci, sehingga dua penerbitan pertama
     * dalam satu bulan bisa menyusun urutan yang sama. Unique index pada
     * `invoice_number` yang menangkapnya, dan yang kalah mengulang dengan
     * urutan berikutnya. Disalin dari pola generator kode booking di F1.4 —
     * termasuk batas percobaannya, supaya kegagalan yang sesungguhnya tidak
     * berputar selamanya.
     *
     * @param  Closure(): Invoice  $callback
     */
    private function transaksiNomorUnik(Closure $callback): Invoice
    {
        $percobaan = 0;

        while (true) {
            try {
                return DB::transaction($callback);
            } catch (UniqueConstraintViolationException $e) {
                if (++$percobaan >= 5) {
                    throw $e;
                }
            }
        }
    }
}
