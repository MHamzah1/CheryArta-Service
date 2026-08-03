<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BookingSource;
use App\Models\Booking;
use App\Support\SlotTime;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Config;

/**
 * SATU-SATUNYA tempat aturan slot hidup (docs/03 §3.6, docs/05 §5.1).
 *
 * Sistem lama menuliskan aturan yang sama di tiga tempat dalam satu berkas HTML
 * dan akhirnya saling bertabrakan (temuan B2). Karena itu tidak ada satu pun
 * angka aturan di kelas ini — semuanya dibaca dari `config/booking.php`, dan
 * tidak ada percabangan "kalau hari Sabtu" yang ditulis tangan.
 *
 * Service tidak menyentuh request(), session(), atau auth()
 * (.claude/rules/10-backend-laravel.md).
 */
class SlotService
{
    /** Hari ini menurut zona waktu bengkel, bukan zona server (temuan B7). */
    public function today(): CarbonImmutable
    {
        return CarbonImmutable::now($this->timezone())->startOfDay();
    }

    public function parseDate(string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date, $this->timezone())->startOfDay();
    }

    /**
     * Tanggal paling awal yang boleh dipesan.
     *
     * Booking walk-in dibuat admin untuk pelanggan yang sudah berdiri di depan
     * meja, jadi aturan H-1 tidak berlaku untuknya — kuota tetap berlaku.
     */
    public function earliestDate(BookingSource $source = BookingSource::Web): CarbonImmutable
    {
        return $source->bypassesLeadTime()
            ? $this->today()
            : $this->today()->addDays(Config::integer('booking.lead_time_days'));
    }

    /** Batas terjauh pemesanan ke depan. */
    public function latestDate(): CarbonImmutable
    {
        return $this->today()->addDays(Config::integer('booking.max_advance_days'));
    }

    public function isClosedOn(CarbonImmutable $date): bool
    {
        return in_array($date->dayOfWeek, Config::array('booking.closed_weekdays'), true);
    }

    /**
     * Daftar slot untuk satu tanggal, dalam bentuk `H:i`.
     *
     * Penimpaan per hari dibaca dari `slots_by_weekday` bila tersedia — inilah
     * yang membuat slot Sabtu berhenti di 13:00 (keputusan R7) tanpa satu pun
     * `if` tentang hari di dalam kode.
     *
     * @return list<string>
     */
    public function slotsOn(CarbonImmutable $date): array
    {
        if ($this->isClosedOn($date)) {
            return [];
        }

        /** @var array<int, list<string>> $perHari */
        $perHari = Config::array('booking.slots_by_weekday');

        /** @var list<string> $slots */
        $slots = $perHari[$date->dayOfWeek] ?? Config::array('booking.slots');

        return array_map(SlotTime::short(...), $slots);
    }

    /**
     * Alasan sebuah tanggal tidak bisa dipesan, atau null bila boleh.
     *
     * Mengembalikan kalimat siap tampil, bukan kode galat: pesan yang
     * menjelaskan apa yang harus dilakukan adalah syarat di
     * .claude/rules/40-ui-design-system.md.
     */
    public function dateRejectionReason(CarbonImmutable $date, BookingSource $source = BookingSource::Web): ?string
    {
        if ($this->isClosedOn($date)) {
            return 'Bengkel tutup pada hari '.$this->namaHari($date).'. Silakan pilih hari lain.';
        }

        $earliest = $this->earliestDate($source);

        if ($date->lessThan($earliest)) {
            return 'Booking paling cepat untuk '.$this->tanggalIndonesia($earliest)
                .'. Pemesanan dibutuhkan minimal H-'.Config::integer('booking.lead_time_days').'.';
        }

        if ($date->greaterThan($this->latestDate())) {
            return 'Booking paling jauh '.Config::integer('booking.max_advance_days')
                .' hari ke depan, yaitu sampai '.$this->tanggalIndonesia($this->latestDate()).'.';
        }

        return null;
    }

    public function isBookableDate(CarbonImmutable $date, BookingSource $source = BookingSource::Web): bool
    {
        return $this->dateRejectionReason($date, $source) === null;
    }

    /**
     * Tanggal buka pertama yang boleh dipesan, terhitung dari `earliestDate()`.
     *
     * Dipakai kartu ketersediaan di beranda (docs/06 §6.5): "besok" bukan
     * jawaban yang benar bila besok jatuh pada hari tutup. Pencariannya
     * dibatasi satu putaran minggu — daftar hari tutup tidak mungkin memuat
     * seluruh tujuh hari tanpa membuat bengkel tidak pernah buka.
     */
    public function nextBookableDate(BookingSource $source = BookingSource::Web): CarbonImmutable
    {
        $date = $this->earliestDate($source);

        for ($lompatan = 0; $lompatan < 7; $lompatan++) {
            if (! $this->isClosedOn($date)) {
                return $date;
            }

            $date = $date->addDay();
        }

        return $date;
    }

    /**
     * Ketersediaan setiap slot pada satu tanggal.
     *
     * Dihitung lewat SATU kueri beragregasi, bukan satu kueri per slot —
     * sebelas kueri untuk satu layar adalah cara N+1 masuk lewat pintu belakang.
     *
     * @param  int|null  $ignoreBookingId  Booking yang sedang dijadwal ulang; slotnya sendiri tidak boleh dihitung menghalangi.
     * @return list<array{time: string, remaining: int, is_full: bool}>
     */
    public function availability(CarbonImmutable $date, ?int $ignoreBookingId = null): array
    {
        $quota = Config::integer('booking.quota_per_slot');
        $terpakai = $this->usageOn($date, $ignoreBookingId);

        return array_map(function (string $time) use ($quota, $terpakai): array {
            $sisa = max(0, $quota - ($terpakai[SlotTime::normalize($time)] ?? 0));

            return [
                'time' => $time,
                'remaining' => $sisa,
                'is_full' => $sisa === 0,
            ];
        }, $this->slotsOn($date));
    }

    /** Sisa kuota satu slot. Untuk keputusan menyimpan, pakai BookingService yang mengunci baris. */
    public function remaining(CarbonImmutable $date, string $time, ?int $ignoreBookingId = null): int
    {
        $terpakai = $this->usageOn($date, $ignoreBookingId);

        return max(0, Config::integer('booking.quota_per_slot') - ($terpakai[SlotTime::normalize($time)] ?? 0));
    }

    /** Slot yang masih punya sisa kuota, untuk ditawarkan saat pilihan pengguna penuh. @return list<string> */
    public function openTimesOn(CarbonImmutable $date, ?int $ignoreBookingId = null): array
    {
        return array_values(array_map(
            static fn (array $slot): string => $slot['time'],
            array_filter($this->availability($date, $ignoreBookingId), static fn (array $slot): bool => ! $slot['is_full']),
        ));
    }

    public function isValidTimeOn(CarbonImmutable $date, string $time): bool
    {
        return in_array(SlotTime::short($time), $this->slotsOn($date), true);
    }

    /**
     * Perkiraan jam selesai = jadwal masuk + durasi paket.
     *
     * Dihitung sejak booking dibuat (bukan menunggu status `in_progress`)
     * supaya halaman pelacakan punya jawaban sejak awal — docs/04 §4.2
     * mendefinisikannya sebagai `booking_datetime + estimated_duration_minutes`.
     */
    public function estimatedFinishAt(CarbonImmutable $date, string $time, int $durationMinutes): CarbonImmutable
    {
        return CarbonImmutable::parse(
            $date->format('Y-m-d').' '.SlotTime::normalize($time),
            $this->timezone(),
        )->addMinutes($durationMinutes);
    }

    /**
     * Perkiraan selesai bila pekerjaan dimulai pada titik waktu tertentu.
     *
     * Dipakai saat kendaraan benar-benar masuk (`confirmed → in_progress`):
     * mobil yang baru mulai dikerjakan pukul 10.15 tidak selesai menurut
     * jadwal slot 09:00 (docs/05 §5.3).
     */
    public function estimatedFinishFrom(CarbonImmutable $start, int $durationMinutes): CarbonImmutable
    {
        return $start->setTimezone($this->timezone())->addMinutes($durationMinutes);
    }

    /** Titik waktu "sekarang" menurut zona bengkel. */
    public function now(): CarbonImmutable
    {
        return CarbonImmutable::now($this->timezone());
    }

    /**
     * Jumlah booking yang menempati tiap slot pada satu tanggal.
     *
     * Booking `cancelled` dan `no_show` MELEPAS kuotanya — perilaku yang tidak
     * ada di sistem lama, sehingga slot di sana bisa "penuh palsu"
     * (docs/05 §5.1).
     *
     * @return array<string, int> Berkunci jam bentuk `H:i:s`.
     */
    private function usageOn(CarbonImmutable $date, ?int $ignoreBookingId = null): array
    {
        return Booking::query()
            ->occupyingQuota()
            ->where('booking_date', $date->toDateString())
            ->when($ignoreBookingId !== null, fn ($query) => $query->whereKeyNot($ignoreBookingId))
            ->selectRaw('booking_time, COUNT(*) as jumlah')
            ->groupBy('booking_time')
            ->pluck('jumlah', 'booking_time')
            ->mapWithKeys(fn (int $jumlah, string $time): array => [SlotTime::normalize($time) => $jumlah])
            ->all();
    }

    private function timezone(): string
    {
        return Config::string('booking.timezone');
    }

    private function namaHari(CarbonImmutable $date): string
    {
        return [
            'Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu',
        ][$date->dayOfWeek];
    }

    private function tanggalIndonesia(CarbonImmutable $date): string
    {
        $bulan = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ][$date->month - 1];

        return $this->namaHari($date).', '.$date->day.' '.$bulan.' '.$date->year;
    }
}
