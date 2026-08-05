<?php

declare(strict_types=1);

use App\Enums\WhatsAppTemplateKey;
use App\Models\Booking;
use App\Models\ContactMessage;
use App\Models\Facility;
use App\Models\Faq;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppTemplate;

/*
|--------------------------------------------------------------------------
| A14 — Matriks hak akses sebagai satu berkas (docs/07 §A14, docs/09 §9.3)
|--------------------------------------------------------------------------
|
| Sampai sekarang otorisasi memang ditegakkan, tetapi barisnya tersebar di
| AdminAccessTest, CustomerDirectoryTest, dan uji katalog. Tersebar berarti
| baris yang HILANG tidak kelihatan — dan itulah cara temuan S3 sistem lama
| bisa terulang tanpa ada yang sadar.
|
| Berkas ini menyusunnya sebagai satu uji per baris matriks. Ia sengaja
| ditulis **lebih dulu**, sebelum modul A10/A11/A12 dibangun (keputusan
| grill #1): baris untuk rute yang belum lahir dibiarkan MERAH, bukan
| dilewati atau ditandai skip. Merah di sini adalah daftar pekerjaan yang
| tersisa — kalau ada yang tertinggal saat pekerjaan dikejar cepat, ia
| terlihat sebagai uji gagal, bukan sebagai kekosongan senyap.
|
| Baris template WhatsApp ditambahkan di F2.3.6 (Tahap 10). Baris invoice
| belum ada: modulnya lahir di Tahap 11, dan barisnya menyusul di F2.4.6.
|
*/

beforeEach(fn () => bekukanWaktuUji());
afterEach(fn () => cairkanWaktuUji());

/**
 * Menegaskan satu baris matriks sekaligus untuk keempat peran.
 *
 * Ditulis sebagai satu penolong supaya setiap baris matriks benar-benar
 * menguji **empat** sisi. Menuliskannya manual per baris membuat sisi yang
 * terlewat tidak kelihatan — persis masalah yang berkas ini selesaikan.
 *
 * @param  'get'|'post'|'put'|'delete'  $method
 */
function matriks(string $method, string $url, bool $sa, bool $advisor, bool $customer, bool $tamu): void
{
    // Tamu: ditolak berarti dialihkan ke login, bukan 403.
    test()->{$method}($url)->assertRedirect(route('login'));
    expect($tamu)->toBeFalse('Baris matriks ini menandai tamu boleh — penolongnya belum mendukung itu.');

    $peran = [
        [$sa, fn () => superAdmin(), 'Super Admin'],
        [$advisor, fn () => serviceAdvisor(), 'Service Advisor'],
        [$customer, fn () => customer(), 'Customer'],
    ];

    // Yang DITOLAK diperiksa lebih dulu, yang boleh belakangan. Urutan ini
    // bukan gaya penulisan: pada baris merusak seperti `delete`, peran yang
    // boleh benar-benar menghapus sumber dayanya — dan peran sesudahnya akan
    // menerima 404, bukan 403, sehingga barisnya lulus karena alasan yang
    // salah.
    $urut = [
        ...array_filter($peran, fn (array $p) => ! $p[0]),
        ...array_filter($peran, fn (array $p) => $p[0]),
    ];

    foreach ($urut as [$boleh, $buat, $sebutan]) {
        $respons = test()->actingAs($buat())->{$method}($url);

        // `baseResponse` dipakai langsung karena rute export mengembalikan
        // StreamedResponse, yang tidak punya `status()`.
        $status = $respons->baseResponse->getStatusCode();

        // "Boleh" diperiksa sebagai status di bawah 400, BUKAN sekadar "bukan
        // 403". Versi longgar itu meloloskan 500 — dan sempat benar-benar
        // terjadi: satu rute yang controllernya belum di-import lolos sebagai
        // hijau padahal halamannya galat.
        $boleh
            ? expect($status)->toBeLessThan(400, "{$sebutan} seharusnya boleh membuka {$url}, dapat {$status}")
            : $respons->assertForbidden();
    }
}

/*
|--------------------------------------------------------------------------
| Panel operasional — modulnya sudah ada
|--------------------------------------------------------------------------
*/

it('baris matriks: dashboard — SA & advisor boleh', function () {
    matriks('get', route('admin.dashboard'), sa: true, advisor: true, customer: false, tamu: false);
});

it('baris matriks: jadwal — SA & advisor boleh', function () {
    matriks('get', route('admin.schedule.index'), sa: true, advisor: true, customer: false, tamu: false);
});

it('baris matriks: melihat semua booking — SA & advisor boleh', function () {
    matriks('get', route('admin.bookings.index'), sa: true, advisor: true, customer: false, tamu: false);
});

it('baris matriks: melihat data seluruh customer — SA & advisor boleh', function () {
    matriks('get', route('admin.customers.index'), sa: true, advisor: true, customer: false, tamu: false);
});

it('baris matriks: laporan booking & okupansi — SA & advisor boleh', function () {
    matriks('get', route('admin.reports.index'), sa: true, advisor: true, customer: false, tamu: false);
});

it('baris matriks: export — SA & advisor boleh', function () {
    matriks('get', route('admin.bookings.export'), sa: true, advisor: true, customer: false, tamu: false);
});

it('baris matriks: menghapus booking — SA saja', function () {
    $booking = bookingAdmin();

    matriks('delete', route('admin.bookings.destroy', $booking), sa: true, advisor: false, customer: false, tamu: false);
});

/*
|--------------------------------------------------------------------------
| Master data — SA saja
|--------------------------------------------------------------------------
*/

it('baris matriks: katalog mobil — SA saja', function () {
    matriks('get', route('admin.car-models.index'), sa: true, advisor: false, customer: false, tamu: false);
});

it('baris matriks: paket layanan — SA saja', function () {
    matriks('get', route('admin.service-packages.index'), sa: true, advisor: false, customer: false, tamu: false);
});

/*
|--------------------------------------------------------------------------
| A10 Konten — MERAH sampai modulnya lahir di tahap ini
|--------------------------------------------------------------------------
*/

it('baris matriks: fasilitas — SA saja', function () {
    matriks('get', route('admin.facilities.index'), sa: true, advisor: false, customer: false, tamu: false);
});

it('baris matriks: FAQ — SA saja', function () {
    matriks('get', route('admin.faqs.index'), sa: true, advisor: false, customer: false, tamu: false);
});

it('baris matriks: testimoni — SA saja', function () {
    matriks('get', route('admin.testimonials.index'), sa: true, advisor: false, customer: false, tamu: false);
});

it('baris matriks: pesan masuk — SA saja', function () {
    matriks('get', route('admin.contact-messages.index'), sa: true, advisor: false, customer: false, tamu: false);
});

it('menolak advisor menghapus pesan kontak', function () {
    $pesan = ContactMessage::factory()->create();

    matriks('delete', route('admin.contact-messages.destroy', $pesan), sa: true, advisor: false, customer: false, tamu: false);
});

it('menolak advisor menyunting konten landing', function () {
    $fasilitas = Facility::factory()->create();
    $faq = Faq::factory()->create();
    $testimoni = Testimonial::factory()->create();

    foreach ([
        route('admin.facilities.update', $fasilitas),
        route('admin.faqs.update', $faq),
        route('admin.testimonials.update', $testimoni),
    ] as $url) {
        $this->actingAs(serviceAdvisor())->put($url)->assertForbidden();
    }
});

/*
|--------------------------------------------------------------------------
| A11 Pengguna Internal & A12 Activity Log — MERAH sampai modulnya lahir
|--------------------------------------------------------------------------
*/

it('baris matriks: pengguna internal — SA saja', function () {
    matriks('get', route('admin.users.index'), sa: true, advisor: false, customer: false, tamu: false);
});

it('baris matriks: activity log — SA saja', function () {
    matriks('get', route('admin.activity-log.index'), sa: true, advisor: false, customer: false, tamu: false);
});

it('menolak advisor mengubah akun staf', function () {
    $sasaran = serviceAdvisor();

    matriks('put', route('admin.users.update', $sasaran), sa: true, advisor: false, customer: false, tamu: false);
});

it('menolak pengelolaan akun customer lewat rute pengguna internal', function () {
    // A11 hanya untuk akun staf. Membiarkan sasaran customer lolos di sini
    // berarti ada dua jalur menonaktifkan pelanggan dengan pengaman berbeda
    // (docs/07 §A11).
    $pelanggan = customer();

    $this->actingAs(superAdmin())
        ->put(route('admin.users.update', $pelanggan))
        ->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| A7 Template WhatsApp — SA saja (F2.3.6)
|--------------------------------------------------------------------------
|
| Isi pesan yang dibaca pelanggan hanya boleh diubah Super Admin. Advisor
| MEMAKAI templatenya lewat panel di detail booking — barisnya ada di bawah,
| dan justru harus boleh untuk keduanya.
|
*/

it('baris matriks: daftar template WA — SA saja', function () {
    matriks('get', route('admin.whatsapp-templates.index'), sa: true, advisor: false, customer: false, tamu: false);
});

// Satu `matriks()` per uji: penolongnya memakai actingAs, dan status login itu
// bertahan sampai akhir uji — panggilan kedua akan memeriksa "tamu" sebagai
// pengguna yang masih masuk dari panggilan pertama.
it('baris matriks: form ubah template WA — SA saja', function () {
    $template = WhatsAppTemplate::factory()->key(WhatsAppTemplateKey::BookingConfirmed)->create();

    matriks('get', route('admin.whatsapp-templates.edit', $template), sa: true, advisor: false, customer: false, tamu: false);
});

it('baris matriks: menyimpan template WA — SA saja', function () {
    $template = WhatsAppTemplate::factory()->key(WhatsAppTemplateKey::BookingConfirmed)->create();

    matriks('put', route('admin.whatsapp-templates.update', $template), sa: true, advisor: false, customer: false, tamu: false);
});

it('baris matriks: mencatat pesan WhatsApp — SA & advisor boleh', function () {
    $booking = Booking::factory()->create();

    matriks('post', route('admin.bookings.whatsapp.store', $booking), sa: true, advisor: true, customer: false, tamu: false);
});

it('menolak customer menandai pesan pada booking miliknya sendiri', function () {
    // Pemilik booking boleh MELIHAT bookingnya, tetapi jejak pengiriman adalah
    // catatan internal bengkel — bukan sesuatu yang ditandai pelanggan.
    $pemilik = customer();
    $booking = Booking::factory()->create(['user_id' => $pemilik->id]);
    $pesan = WhatsAppMessage::factory()->create(['booking_id' => $booking->id]);

    $this->actingAs($pemilik)
        ->put(route('admin.bookings.whatsapp.sent', [$booking, $pesan]))
        ->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| Area customer — staf tidak berlaku di sini
|--------------------------------------------------------------------------
*/

it('baris matriks: kelola kendaraan sendiri — customer saja', function () {
    matriks('get', route('customer.vehicles.index'), sa: false, advisor: false, customer: true, tamu: false);
});

it('baris matriks: buat booking lewat layar customer — customer saja', function () {
    // Staf membuat booking lewat walk-in di panel (admin.bookings.create),
    // bukan lewat form pelanggan. Barisnya "Membuat booking ✅ SA/ADV" tetap
    // terpenuhi di sana, bukan di sini.
    matriks('get', route('customer.booking.create'), sa: false, advisor: false, customer: true, tamu: false);
});

it('baris matriks: riwayat servis sendiri — customer saja', function () {
    matriks('get', route('customer.history.index'), sa: false, advisor: false, customer: true, tamu: false);
});

it('mengizinkan staf membuat booking lewat walk-in', function () {
    $this->actingAs(serviceAdvisor())->get(route('admin.bookings.create'))->assertOk();
    $this->actingAs(superAdmin())->get(route('admin.bookings.create'))->assertOk();
});

/*
|--------------------------------------------------------------------------
| Halaman publik — terbuka untuk semua, termasuk tamu
|--------------------------------------------------------------------------
*/

it('baris matriks: halaman publik terbuka untuk seluruh peran', function () {
    $publik = [
        route('home'),
        route('public.catalog.index'),
        route('public.faq'),
        route('public.services'),
        route('public.tracking'),
    ];

    foreach ($publik as $url) {
        $this->get($url)->assertOk();

        foreach ([superAdmin(), serviceAdvisor(), customer()] as $pengguna) {
            $this->actingAs($pengguna)->get($url)->assertOk();
        }
    }
});

/*
|--------------------------------------------------------------------------
| Akun nonaktif — ditolak di mana pun, apa pun perannya
|--------------------------------------------------------------------------
*/

it('menolak akun nonaktif di seluruh area ber-peran', function () {
    $this->actingAs(superAdmin(['is_active' => false]))
        ->get(route('admin.dashboard'))->assertForbidden();

    $this->actingAs(serviceAdvisor(['is_active' => false]))
        ->get(route('admin.bookings.index'))->assertForbidden();

    $this->actingAs(customer(['is_active' => false]))
        ->get(route('dashboard'))->assertForbidden();
});

it('memastikan seluruh peran yang dikenali sistem ikut diuji di berkas ini', function () {
    // Penjaga terhadap penambahan role baru tanpa barisnya di matriks. Bila
    // suatu hari ada role keempat, uji ini gagal dan memaksa matriksnya
    // ditinjau — bukan dibiarkan lolos diam-diam.
    expect(array_map(fn ($r) => $r->value, App\Enums\UserRole::cases()))
        ->toEqualCanonicalizing(['super_admin', 'service_advisor', 'customer']);

    expect(User::query()->count())->toBe(0);
});
