<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Pekerjaan terjadwal
|--------------------------------------------------------------------------
|
| Retensi activity log 12 bulan (docs/07 §A12, docs/09 §9.7). Batas harinya
| ada di config/activitylog.php, bukan di sini — angka aturan hanya boleh
| hidup di satu tempat (cacat B2 sistem lama).
|
| PERINGATAN: mendaftarkan perintah TIDAK membuatnya berjalan. Ia butuh proses
| penjadwal (`schedule:work` atau cron) yang sampai sekarang belum ada di
| Railway. Mengaktifkannya adalah pekerjaan Tahap 12 (keputusan grill #7):
| dengan volume satu bengkel, log yang menumpuk beberapa bulan tidak
| membahayakan apa pun, sedangkan menyiapkan proses terpisah adalah urusan
| infrastruktur tersendiri.
|
| Didaftarkan sekarang supaya niatnya terekam di kode dan tidak terlupakan —
| retensi yang hanya tertulis di dokumen adalah janji kosong.
|
*/

Schedule::command('activitylog:clean')
    ->daily()
    ->at('02:00')
    ->timezone(config('booking.timezone'));
