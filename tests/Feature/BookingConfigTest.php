<?php

declare(strict_types=1);

/*
| Aturan slot sistem lama dipertahankan PERSIS (keputusan rancangan #7) dan
| kini hanya hidup di satu tempat: config/booking.php.
|
| Sistem lama menuliskan aturan ini di tiga tempat berbeda dalam satu berkas
| HTML dan akhirnya saling bertabrakan — temuan B2 pada
| docs/01-analisis-sistem-lama.md.
*/

it('mempertahankan kuota dua booking per slot jam', function () {
    expect(config('booking.quota_per_slot'))->toBe(2);
});

it('mempertahankan kewajiban booking H-1', function () {
    expect(config('booking.lead_time_days'))->toBe(1);
});

it('menutup hari Minggu', function () {
    expect(config('booking.closed_weekdays'))->toContain(0);
});

it('mempertahankan sebelas slot waktu sistem lama', function () {
    expect(config('booking.slots'))->toBe([
        '08:00', '08:30', '09:00', '09:30', '10:00', '10:30',
        '11:00', '11:30', '13:00', '13:30', '14:00',
    ]);
});

it('menghentikan slot Sabtu di 13:00', function () {
    // Keputusan R7 (3 Agustus 2026) — satu-satunya penyimpangan yang disengaja
    // dari keputusan #7, karena slot 14:00 menjanjikan pekerjaan yang baru
    // dimulai tepat saat bengkel tutup. Lihat docs/05 §Catatan konflik jam Sabtu.
    expect(config('booking.slots_by_weekday.6'))->toBe([
        '08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '13:00',
    ]);
});

it('tidak mengubah slot hari kerja lain lewat penimpaan Sabtu', function () {
    expect(array_keys(config('booking.slots_by_weekday')))->toBe([6]);
});

it('tidak menyediakan slot pada jam istirahat 12:00-13:00', function () {
    expect(config('booking.slots'))
        ->not->toContain('12:00')
        ->not->toContain('12:30');
});

it('memakai zona waktu Asia/Jakarta', function () {
    // Sistem lama memakai UTC lewat toISOString() dan meleset satu hari
    // saat diakses dini hari (temuan B7).
    expect(config('booking.timezone'))->toBe('Asia/Jakarta')
        ->and(config('app.timezone'))->toBe('Asia/Jakarta');
});

it('memuat profil perusahaan sesuai sistem lama', function () {
    expect(config('company.name'))->toBe('Chery Arta')
        ->and(config('company.address.full'))
        ->toBe('Jl. Jend. Sudirman No.1, Kranji, Kec. Bekasi Bar., Kota Bekasi, Jawa Barat')
        ->and(config('company.phones.landline'))->toBe('(021) 38317201')
        ->and(config('company.phones.mobile'))->toBe('0895-4045-46904')
        ->and(config('company.email'))->toBe('Cheryaftersales.10254719@gmail.com')
        ->and(config('company.experience_years'))->toBe(15)
        ->and(config('company.operating_hours_text'))->toBe([
            'Senin - Jumat: 08:00 - 16:00',
            'Sabtu: 08:00 - 14:00',
            'Minggu: Tutup',
        ]);
});
