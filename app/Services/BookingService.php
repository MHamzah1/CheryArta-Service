<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Exceptions\SlotUnavailableException;
use App\Models\Booking;
use App\Models\ServicePackage;
use App\Models\User;
use App\Support\SlotTime;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Pembuatan, penjadwalan ulang, dan pembatalan booking (docs/05 §5.2 & §5.4).
 *
 * Tiga hal yang HANYA boleh terjadi di sini, tidak di controller:
 * penerbitan kode booking, penetapan status, dan pencatatan riwayat status.
 * Ketiganya ditentukan server — menerimanya dari request adalah larangan
 * mutlak (.claude/rules/50-keamanan.md).
 */
class BookingService
{
    public function __construct(private readonly SlotService $slots) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  User|null  $actor  Pelaku perubahan; null berarti customer sendiri.
     */
    public function create(
        User $customer,
        array $data,
        BookingSource $source = BookingSource::Web,
        ?User $actor = null,
    ): Booking {
        return $this->transaksiKodeUnik(function () use ($customer, $data, $source, $actor): Booking {
            $booking = $this->simpanBooking($customer, $data, $source);

            $this->catatRiwayat($booking, null, BookingStatus::Pending, $actor, 'Booking dibuat.');

            return $booking;
        });
    }

    /**
     * Menjadwal ulang: booking lama dibatalkan, booking baru dibuat menunjuk
     * ke sana lewat `rescheduled_from_id`.
     *
     * Alasan tidak sekadar mengubah tanggal booking lama (docs/05 §5.4):
     * riwayat jadwal tetap terlihat, dan advisor bisa mengetahui pelanggan
     * yang sering menggeser jadwal.
     *
     * @param  array<string, mixed>  $data
     */
    public function reschedule(Booking $booking, array $data, ?User $actor = null): Booking
    {
        return $this->transaksiKodeUnik(function () use ($booking, $data, $actor): Booking {
            $jadwalLama = $this->labelJadwal($booking);

            $baru = $this->simpanBooking(
                $booking->user,
                [
                    'vehicle_id' => $booking->vehicle_id,
                    'service_package_id' => $booking->service_package_id,
                    'odometer' => $booking->odometer,
                    'complaint' => $booking->complaint,
                    ...$data,
                    'rescheduled_from_id' => $booking->getKey(),
                ],
                $booking->source,
                // Slot booking lama tidak boleh menghalangi dirinya sendiri:
                // ia dilepas dalam transaksi yang sama, beberapa baris di bawah.
                abaikanBookingId: $booking->getKey(),
            );

            $this->tandaiBatal(
                $booking,
                "Dijadwalkan ulang ke {$this->labelJadwal($baru)}.",
                $actor,
            );

            $this->catatRiwayat(
                $baru,
                null,
                BookingStatus::Pending,
                $actor,
                "Dijadwalkan ulang dari {$jadwalLama}.",
            );

            return $baru;
        });
    }

    /** Pembatalan mandiri maupun oleh advisor. Alasan selalu wajib (docs/05 §5.4). */
    public function cancel(Booking $booking, string $reason, ?User $actor = null): Booking
    {
        return DB::transaction(function () use ($booking, $reason, $actor): Booking {
            $this->tandaiBatal($booking, $reason, $actor);

            return $booking;
        });
    }

    /**
     * Nomor urut kode booking: `CA-YYYYMMDD-NNNN`.
     *
     * Dipanggil HANYA dari dalam transaksi milik pemanggilnya, dengan baris
     * hari itu terkunci — dua permintaan bersamaan pada tanggal yang sama
     * kalau tidak akan menerbitkan nomor kembar dan salah satunya gagal di
     * unique index.
     *
     * Booking yang sudah dihapus lunak ikut diperiksa: kodenya tidak boleh
     * dipakai ulang, karena masih tercetak di berkas pelanggan.
     */
    private function terbitkanKode(CarbonImmutable $date): string
    {
        $awalan = Config::string('booking.code_prefix').'-'.$date->format('Ymd').'-';

        $terakhir = Booking::withTrashed()
            ->where('booking_code', 'like', $awalan.'%')
            ->lockForUpdate()
            ->orderByDesc('booking_code')
            ->value('booking_code');

        $urut = $terakhir === null ? 1 : ((int) substr($terakhir, -4)) + 1;

        return $awalan.Str::padLeft((string) $urut, 4, '0');
    }

    /**
     * Transaksi yang tahan terhadap tabrakan nomor urut kode booking.
     *
     * `terbitkanKode()` mengunci baris terakhir hari itu, tetapi baris yang
     * BELUM ADA tidak bisa dikunci: dua permintaan pertama pada tanggal yang
     * sama sama-sama melihat "belum ada kode hari ini" dan sama-sama menyusun
     * `-0001`. Unique index menangkapnya, lalu yang kalah mengulang seluruh
     * transaksi — kali ini ia sudah melihat kode lawannya.
     *
     * Mengulang aman karena seluruh isi transaksi memang sudah di-rollback,
     * termasuk pemeriksaan kuotanya.
     *
     * @param  Closure(): Booking  $callback
     */
    private function transaksiKodeUnik(Closure $callback): Booking
    {
        $percobaanMaksimal = 3;

        for ($percobaan = 1; ; $percobaan++) {
            try {
                return DB::transaction($callback);
            } catch (UniqueConstraintViolationException $e) {
                if ($percobaan >= $percobaanMaksimal) {
                    throw $e;
                }
            }
        }
    }

    /**
     * Inti penyimpanan, dipakai pembuatan biasa maupun penjadwalan ulang.
     *
     * @param  array<string, mixed>  $data
     */
    private function simpanBooking(
        User $customer,
        array $data,
        BookingSource $source,
        ?int $abaikanBookingId = null,
    ): Booking {
        $date = $this->slots->parseDate((string) $data['booking_date']);
        $time = SlotTime::short((string) $data['booking_time']);

        $this->pastikanSlotMasihAda($date, $time, $abaikanBookingId);

        $package = ServicePackage::query()->findOrFail($data['service_package_id']);

        $booking = new Booking([
            'vehicle_id' => $data['vehicle_id'],
            'service_package_id' => $package->getKey(),
            'booking_date' => $date->toDateString(),
            'booking_time' => $time,
            'source' => $source,
            'odometer' => $data['odometer'] ?? null,
            'complaint' => $data['complaint'] ?? null,
            'rescheduled_from_id' => $data['rescheduled_from_id'] ?? null,
            'estimated_finish_at' => $this->slots->estimatedFinishAt(
                $date,
                $time,
                $package->estimated_duration_minutes,
            ),
        ]);

        // Status awal `pending`: perubahan dari sistem lama yang langsung
        // menandai booking "Successful" tanpa konfirmasi manusia (docs/05 §5.1).
        $booking->status = BookingStatus::Pending;
        $booking->booking_code = $this->terbitkanKode($date);

        // Lewat relasi, sehingga `user_id` mustahil datang dari request.
        $customer->bookings()->save($booking);

        return $booking;
    }

    /**
     * Pemeriksaan kuota yang menentukan — dijalankan di dalam transaksi dengan
     * baris slotnya dikunci.
     *
     * Sistem lama membaca jumlah booking lalu menulis tanpa penguncian, jadi
     * dua orang yang menekan tombol bersamaan bisa membuat slot terisi tiga
     * (docs/05 §5.1).
     */
    private function pastikanSlotMasihAda(CarbonImmutable $date, string $time, ?int $abaikanBookingId): void
    {
        $terpakai = Booking::query()
            ->occupyingQuota()
            ->onSlot($date->toDateString(), $time)
            ->when($abaikanBookingId !== null, fn ($query) => $query->whereKeyNot($abaikanBookingId))
            ->lockForUpdate()
            ->count();

        if ($terpakai < Config::integer('booking.quota_per_slot')) {
            return;
        }

        throw new SlotUnavailableException($this->pesanSlotPenuh($date, $time, $abaikanBookingId));
    }

    /** Pesan yang menyebutkan jam pengganti, bukan sekadar "slot penuh" (docs/05 §5.8). */
    private function pesanSlotPenuh(CarbonImmutable $date, string $time, ?int $abaikanBookingId): string
    {
        $tersisa = $this->slots->openTimesOn($date, $abaikanBookingId);

        $pesan = "Jam {$time} pada tanggal tersebut sudah penuh.";

        return $tersisa === []
            ? $pesan.' Tidak ada jam lain yang tersisa pada hari itu — silakan pilih tanggal lain.'
            : $pesan.' Jam yang masih tersedia: '.implode(', ', $tersisa).'.';
    }

    private function tandaiBatal(Booking $booking, string $reason, ?User $actor): void
    {
        $sebelumnya = $booking->status;

        $booking->forceFill([
            'status' => BookingStatus::Cancelled,
            'cancelled_at' => now(),
            'cancel_reason' => Str::limit($reason, 255, ''),
        ])->save();

        $this->catatRiwayat($booking, $sebelumnya, BookingStatus::Cancelled, $actor, $reason);
    }

    /** Setiap perubahan status meninggalkan tepat satu baris riwayat (docs/05 §5.3). */
    private function catatRiwayat(
        Booking $booking,
        ?BookingStatus $from,
        BookingStatus $to,
        ?User $actor,
        ?string $note,
    ): void {
        $booking->statusHistories()->create([
            'from_status' => $from,
            'to_status' => $to,
            'changed_by' => $actor?->getKey(),
            'note' => $note === null ? null : Str::limit($note, 255, ''),
        ]);
    }

    private function labelJadwal(Booking $booking): string
    {
        return $booking->booking_date->format('d/m/Y').' pukul '.SlotTime::short($booking->booking_time);
    }
}
