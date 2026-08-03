<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Exceptions\InvalidStatusTransitionException;
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
     * Booking walk-in: pelanggan sudah berdiri di depan meja, advisor yang
     * mengisikan (docs/07 §A2).
     *
     * Dua bedanya dari `create()`, keduanya disengaja:
     * 1. Aturan H-1 dilewati — ditentukan `BookingSource::WalkIn`, bukan oleh
     *    percabangan di sini. Kuota slot TETAP berlaku.
     * 2. Statusnya langsung `confirmed`: tidak ada gunanya menunggu konfirmasi
     *    advisor atas booking yang baru saja ia buat sendiri.
     *
     * @param  array<string, mixed>  $data
     */
    public function createWalkIn(User $customer, array $data, User $actor): Booking
    {
        return $this->transaksiKodeUnik(function () use ($customer, $data, $actor): Booking {
            $booking = $this->simpanBooking($customer, $data, BookingSource::WalkIn);

            $booking->forceFill([
                'status' => BookingStatus::Confirmed,
                'confirmed_at' => now(),
                'handled_by' => $actor->getKey(),
            ])->save();

            // Satu baris riwayat, bukan dua: bookingnya memang lahir dalam
            // keadaan terkonfirmasi — mencatat "pending" yang tidak pernah
            // benar-benar ada hanya membuat timeline berbohong.
            $this->catatRiwayat(
                $booking,
                null,
                BookingStatus::Confirmed,
                $actor,
                'Booking dibuat di bengkel dan langsung dikonfirmasi.',
            );

            return $booking;
        });
    }

    /**
     * Perpindahan status oleh advisor (docs/05 §5.3).
     *
     * Yang sah ditentukan App\Enums\BookingStatus — kelas ini tidak menyimpan
     * satu pun aturan transisi sendiri. Pemeriksaannya berada di dalam
     * transaksi supaya dua advisor yang menekan tombol bersamaan tidak
     * dua-duanya lolos membaca status lama.
     *
     * @param  string|null  $note  Catatan advisor; wajib saat membatalkan, ikut terlihat customer.
     */
    public function changeStatus(
        Booking $booking,
        BookingStatus $target,
        User $actor,
        ?string $note = null,
        ?int $odometer = null,
    ): Booking {
        return DB::transaction(function () use ($booking, $target, $actor, $note, $odometer): Booking {
            // Barisnya dibaca ULANG dengan kunci, bukan dipercaya dari objek
            // yang dibawa controller: dua advisor bisa membuka booking yang
            // sama, dan yang kedua harus melihat hasil perubahan yang pertama.
            /** @var Booking $terkini */
            $terkini = Booking::query()->lockForUpdate()->findOrFail($booking->getKey());
            $asal = $terkini->status;

            if (! $asal->canTransitionTo($target)) {
                throw InvalidStatusTransitionException::between($asal, $target);
            }

            if ($odometer !== null) {
                $terkini->odometer = $odometer;
            }

            $terkini->forceFill([
                'status' => $target,
                'handled_by' => $terkini->handled_by ?? $actor->getKey(),
                ...$this->efekSamping($terkini, $target, $note),
            ])->save();

            // Odometer kendaraan baru diperbarui saat pekerjaan selesai:
            // angka yang dicatat saat kendaraan masuk masih bisa terkoreksi
            // di meja servis (docs/05 §5.3).
            if ($target === BookingStatus::Completed && $terkini->odometer !== null) {
                $this->perbaruiOdometerKendaraan($terkini);
            }

            $this->catatRiwayat($terkini, $asal, $target, $actor, $note);

            return $terkini;
        });
    }

    /**
     * Kolom penanda waktu yang ikut terisi pada tiap status (docs/05 §5.3).
     *
     * @return array<string, mixed>
     */
    private function efekSamping(Booking $booking, BookingStatus $target, ?string $note): array
    {
        return match ($target) {
            BookingStatus::Confirmed => ['confirmed_at' => now()],

            // Perkiraan selesai dihitung ULANG dari waktu kendaraan benar-benar
            // masuk. Nilai yang dipasang saat booking dibuat berangkat dari jam
            // slot, dan mobil yang masuk pukul 10.15 tidak selesai menurut
            // jadwal 09:00 (janji yang ditinggalkan F1.4).
            BookingStatus::InProgress => [
                'started_at' => now(),
                'estimated_finish_at' => $this->slots->estimatedFinishFrom(
                    $this->slots->now(),
                    $booking->servicePackage->estimated_duration_minutes,
                ),
            ],

            BookingStatus::Completed => ['completed_at' => now()],

            BookingStatus::Cancelled => [
                'cancelled_at' => now(),
                'cancel_reason' => Str::limit((string) $note, 255, ''),
            ],

            default => [],
        };
    }

    /**
     * Odometer kendaraan hanya boleh maju. Servis yang diinput belakangan
     * dengan angka lebih kecil tidak boleh memundurkan catatan kendaraan.
     */
    private function perbaruiOdometerKendaraan(Booking $booking): void
    {
        $vehicle = $booking->vehicle;

        if ($vehicle->last_odometer !== null && $vehicle->last_odometer >= $booking->odometer) {
            return;
        }

        $vehicle->forceFill(['last_odometer' => $booking->odometer])->save();
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
