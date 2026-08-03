<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Models\Booking;

/*
| A1 — Dashboard admin (roadmap 2.1.1–2.1.3, docs/07 §A1).
|
| Yang dijaga di sini: batas tanggal tiap KPI, deret tren yang lengkap termasuk
| hari bernilai nol, dan peringatan booking `confirmed` yang tanggalnya lewat —
| satu-satunya tempat di aplikasi yang menampilkannya.
|
| Waktu dibekukan pada UJI_HARI_INI (Senin, 3 Agustus 2026) supaya "hari ini"
| dan "bulan ini" punya arti yang tetap.
*/

beforeEach(fn () => bekukanWaktuUji());
afterEach(fn () => cairkanWaktuUji());

function bookingDasbor(string $tanggal, BookingStatus $status, string $jam = '09:00'): Booking
{
    $pelanggan = pelangganSiapBooking();

    return Booking::factory()->create([
        'user_id' => $pelanggan->id,
        'vehicle_id' => $pelanggan->vehicles()->value('id'),
        'booking_date' => $tanggal,
        'booking_time' => $jam,
        'status' => $status,
    ]);
}

it('mengganti redirect lama dengan dashboard di /admin', function () {
    // Sebelum F2.1, /admin mengalihkan ke /admin/bookings (keputusan R8).
    $this->actingAs(serviceAdvisor())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/dashboard')
            ->where('today', UJI_HARI_INI));
});

it('menghitung booking hari ini tanpa yang dibatalkan', function () {
    bookingDasbor(UJI_HARI_INI, BookingStatus::Confirmed);
    bookingDasbor(UJI_HARI_INI, BookingStatus::InProgress, '10:00');
    bookingDasbor(UJI_HARI_INI, BookingStatus::Cancelled, '11:00');
    bookingDasbor(UJI_BESOK, BookingStatus::Confirmed);

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.dashboard'))
        ->assertInertia(fn ($page) => $page->where('kpi.booking_hari_ini', 2));
});

it('menghitung seluruh pending yang tanggalnya belum lewat sebagai perlu konfirmasi', function () {
    // Keputusan grill #3: angka yang menyembunyikan booking minggu depan
    // membuat orang merasa sudah beres padahal belum.
    bookingDasbor(UJI_HARI_INI, BookingStatus::Pending);
    bookingDasbor(UJI_BESOK, BookingStatus::Pending);
    bookingDasbor('2026-08-20', BookingStatus::Pending);
    bookingDasbor('2026-08-01', BookingStatus::Pending); // sudah lewat

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.dashboard'))
        ->assertInertia(fn ($page) => $page->where('kpi.perlu_konfirmasi', 3));
});

it('menghitung sedang dikerjakan tanpa batas tanggal', function () {
    // Unit yang menginap di bengkel tetap harus terhitung.
    bookingDasbor('2026-08-01', BookingStatus::InProgress);
    bookingDasbor(UJI_HARI_INI, BookingStatus::InProgress, '10:00');

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.dashboard'))
        ->assertInertia(fn ($page) => $page->where('kpi.sedang_dikerjakan', 2));
});

it('membatasi selesai bulan ini pada bulan kalender berjalan', function () {
    bookingDasbor('2026-08-01', BookingStatus::Completed);
    bookingDasbor(UJI_HARI_INI, BookingStatus::Completed, '10:00');
    bookingDasbor('2026-07-31', BookingStatus::Completed); // bulan lalu

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.dashboard'))
        ->assertInertia(fn ($page) => $page->where('kpi.selesai_bulan_ini', 2));
});

it('membangun deret tren lengkap termasuk hari tanpa booking', function () {
    // Hari kosong yang hilang dari sumbu X membuat garisnya berbohong:
    // penurunan terlihat seperti kekosongan.
    bookingDasbor(UJI_HARI_INI, BookingStatus::Confirmed);

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('trendDays', 30)
            ->has('trend', 30)
            ->where('trend.29.date', UJI_HARI_INI)
            ->where('trend.29.count', 1)
            ->where('trend.0.date', '2026-07-05')
            ->where('trend.0.count', 0));
});

it('memakai booking_date untuk tren, bukan tanggal pembuatan', function () {
    // Keputusan grill #2: dashboard menjawab beban bengkel, bukan tren
    // permintaan masuk.
    $booking = bookingDasbor('2026-07-10', BookingStatus::Completed);
    $booking->forceFill(['created_at' => now()])->save();

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('trend.5.date', '2026-07-10')
            ->where('trend.5.count', 1)
            ->where('trend.29.count', 0));
});

it('menampilkan booking hari ini urut jam tanpa yang dibatalkan', function () {
    bookingDasbor(UJI_HARI_INI, BookingStatus::Confirmed, '11:00');
    bookingDasbor(UJI_HARI_INI, BookingStatus::Pending, '08:00');
    bookingDasbor(UJI_HARI_INI, BookingStatus::Cancelled, '09:00');

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.dashboard'))
        ->assertInertia(fn ($page) => $page
            ->has('todayBookings', 2)
            ->where('todayBookings.0.booking_time', '08:00')
            ->where('todayBookings.1.booking_time', '11:00'));
});

it('mengirim aksi cepat yang sah saja untuk tiap baris', function () {
    // Daftarnya dihitung server dari state machine — React tidak pernah
    // menyimpulkannya sendiri (keputusan grill #5).
    bookingDasbor(UJI_HARI_INI, BookingStatus::Pending);

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.dashboard'))
        ->assertInertia(fn ($page) => $page
            // Dari `pending` hanya Konfirmasi yang muncul; `cancelled` sengaja
            // tidak pernah jadi aksi cepat karena wajib beralasan.
            ->has('todayBookings.0.quick_actions', 1)
            ->where('todayBookings.0.quick_actions.0.value', 'confirmed')
            ->where('todayBookings.0.quick_actions.0.label', 'Konfirmasi'));
});

it('tidak mengirim aksi cepat untuk status yang sudah berakhir', function () {
    bookingDasbor(UJI_HARI_INI, BookingStatus::Completed);

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.dashboard'))
        ->assertInertia(fn ($page) => $page->has('todayBookings.0.quick_actions', 0));
});

it('memperingatkan booking dikonfirmasi yang tanggalnya sudah lewat', function () {
    // Inilah yang sebelumnya tidak terlihat di mana pun: tidak muncul di
    // jadwal hari ini, dan tenggelam di daftar booking.
    bookingDasbor('2026-08-01', BookingStatus::Confirmed);
    bookingDasbor('2026-07-28', BookingStatus::Confirmed);

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.dashboard'))
        ->assertInertia(fn ($page) => $page
            ->has('overdue', 2)
            // Terlama lebih dulu — yang paling lama tertinggal paling mendesak.
            ->where('overdue.0.booking_date', '2026-07-28')
            ->where('overdue.1.booking_date', '2026-08-01'));
});

it('tidak memperingatkan booking hari ini yang masih dikonfirmasi', function () {
    // Ambangnya tanggal, bukan jam (keputusan grill #4).
    bookingDasbor(UJI_HARI_INI, BookingStatus::Confirmed, '08:00');

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.dashboard'))
        ->assertInertia(fn ($page) => $page->has('overdue', 0));
});

it('menyediakan aksi Tidak Hadir pada peringatan', function () {
    bookingDasbor('2026-08-01', BookingStatus::Confirmed);

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('overdue.0.quick_actions', fn ($aksi) => collect($aksi)->pluck('value')->contains('no_show')));
});

it('mengambil okupansi dari sumber yang sama dengan halaman jadwal', function () {
    bookingDasbor(UJI_HARI_INI, BookingStatus::Confirmed, '08:00');
    bookingDasbor(UJI_HARI_INI, BookingStatus::Confirmed, '08:00');

    $this->actingAs(serviceAdvisor())
        ->get(route('admin.dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('occupancy.quota_per_slot', 2)
            ->where('occupancy.closed_reason', null)
            ->has('occupancy.slots', 11)
            ->where('occupancy.slots.0.time', '08:00')
            ->where('occupancy.slots.0.is_full', true)
            ->where('occupancy.slots.0.remaining', 0));
});

it('menampilkan dashboard yang sama untuk Super Admin dan advisor', function () {
    // Tidak satu pun elemen dashboard menyentuh pendapatan, jadi tidak ada
    // yang perlu disembunyikan dari advisor (docs/09 §9.3).
    bookingDasbor(UJI_HARI_INI, BookingStatus::Confirmed);

    $advisor = $this->actingAs(serviceAdvisor())->get(route('admin.dashboard'));
    $sa = $this->actingAs(superAdmin())->get(route('admin.dashboard'));

    expect($sa->getStatusCode())->toBe($advisor->getStatusCode())->toBe(200);
});

it('menolak customer di dashboard', function () {
    $this->actingAs(customer())
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

it('mengantar tamu ke halaman masuk', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
});
