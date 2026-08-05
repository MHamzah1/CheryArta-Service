<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Enums\WhatsAppTemplateKey;
use App\Models\Booking;
use App\Models\BookingStatusHistory;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppTemplate;

/*
|--------------------------------------------------------------------------
| A7/A1 — Kartu "Belum dikabari" & saringan wa=belum (roadmap 2.3.5)
|--------------------------------------------------------------------------
|
| Definisinya hidup di satu tempat — Booking::scopeAwaitingWhatsApp() — dan
| dipakai kartu dashboard MAUPUN saringan daftar booking. Uji di bawah menembak
| keduanya lewat rute sungguhan supaya angka kartu dan isi daftar terbukti
| berasal dari aturan yang sama.
|
*/

beforeEach(function () {
    bekukanWaktuUji();

    $this->advisor = serviceAdvisor();

    foreach (WhatsAppTemplateKey::cases() as $key) {
        WhatsAppTemplate::factory()->key($key)->create();
    }
});

afterEach(fn () => cairkanWaktuUji());

/** Booking yang statusnya baru saja berubah — bahan dasar hitungan ini. */
function bookingBelumDikabari(BookingStatus $status = BookingStatus::Confirmed, ?string $berubahPada = null): Booking
{
    // Nomor WA dibiarkan dari factory: kolomnya unik, dan menuliskan nomor yang
    // sama untuk dua booking membuat uji gagal karena alasan yang tidak ada
    // hubungannya dengan yang sedang diuji.
    $booking = Booking::factory()->create([
        'user_id' => User::factory()->create()->id,
        'status' => $status,
    ]);

    BookingStatusHistory::query()->where('booking_id', $booking->id)->delete();

    BookingStatusHistory::factory()->create([
        'booking_id' => $booking->id,
        'to_status' => $status,
        'created_at' => $berubahPada ?? now(),
    ]);

    return $booking;
}

it('menghitung booking yang statusnya berubah tetapi belum dikabari', function () {
    bookingBelumDikabari();
    bookingBelumDikabari(BookingStatus::Completed);

    $this->actingAs($this->advisor)
        ->get(route('admin.dashboard'))
        ->assertInertia(fn ($page) => $page->where('awaitingWhatsApp', 2));
});

it('tidak menghitung booking yang pesannya sudah ditandai terkirim', function () {
    $booking = bookingBelumDikabari();

    WhatsAppMessage::factory()->sent()->create([
        'booking_id' => $booking->id,
        'template_key' => WhatsAppTemplateKey::BookingConfirmed,
    ]);

    $this->actingAs($this->advisor)
        ->get(route('admin.dashboard'))
        ->assertInertia(fn ($page) => $page->where('awaitingWhatsApp', 0));
});

it('tidak menghitung booking yang pesannya sengaja dilewati', function () {
    $booking = bookingBelumDikabari();

    WhatsAppMessage::factory()->skipped()->create([
        'booking_id' => $booking->id,
        'template_key' => WhatsAppTemplateKey::BookingConfirmed,
    ]);

    $this->actingAs($this->advisor)
        ->get(route('admin.dashboard'))
        ->assertInertia(fn ($page) => $page->where('awaitingWhatsApp', 0));
});

it('tetap menghitung booking yang draftnya dibuka tetapi belum ditandai', function () {
    // `generated` berarti "WhatsApp sudah dibuka, belum ditandai" — pekerjaannya
    // belum selesai, jadi tagihannya belum boleh hilang.
    $booking = bookingBelumDikabari();

    WhatsAppMessage::factory()->create([
        'booking_id' => $booking->id,
        'template_key' => WhatsAppTemplateKey::BookingConfirmed,
    ]);

    $this->actingAs($this->advisor)
        ->get(route('admin.dashboard'))
        ->assertInertia(fn ($page) => $page->where('awaitingWhatsApp', 1));
});

it('tidak menghitung pesan yang ditandai untuk status yang berbeda', function () {
    // Booking sudah dikabari saat dikonfirmasi, lalu berpindah ke Selesai:
    // kabar untuk status baru itu tetap terutang.
    $booking = bookingBelumDikabari(BookingStatus::Completed);

    WhatsAppMessage::factory()->sent()->create([
        'booking_id' => $booking->id,
        'template_key' => WhatsAppTemplateKey::BookingConfirmed,
    ]);

    $this->actingAs($this->advisor)
        ->get(route('admin.dashboard'))
        ->assertInertia(fn ($page) => $page->where('awaitingWhatsApp', 1));
});

it('tidak menghitung booking yang belum dikonfirmasi', function () {
    // `booking_created` bersifat sukarela: booking yang belum dikonfirmasi
    // belum menjanjikan apa pun kepada pelanggan (keputusan grill Q6).
    bookingBelumDikabari(BookingStatus::Pending);

    $this->actingAs($this->advisor)
        ->get(route('admin.dashboard'))
        ->assertInertia(fn ($page) => $page->where('awaitingWhatsApp', 0));
});

it('tidak menghitung perubahan status yang lebih tua dari rentang', function () {
    // Tanpa batas ini kartunya lahir di angka ratusan pada hari pertama dan
    // tidak akan pernah bisa dikosongkan.
    $lama = now()->subDays((int) config('whatsapp.pending_window_days') + 1);

    bookingBelumDikabari(BookingStatus::Confirmed, $lama->toDateTimeString());

    $this->actingAs($this->advisor)
        ->get(route('admin.dashboard'))
        ->assertInertia(fn ($page) => $page->where('awaitingWhatsApp', 0));
});

it('tidak menghitung booking yang templatenya dinonaktifkan', function () {
    bookingBelumDikabari();

    WhatsAppTemplate::query()->where('key', 'booking_confirmed')->update(['is_active' => false]);

    $this->actingAs($this->advisor)
        ->get(route('admin.dashboard'))
        ->assertInertia(fn ($page) => $page->where('awaitingWhatsApp', 0));
});

it('menyaring daftar booking dengan aturan yang sama seperti kartunya', function () {
    $belum = bookingBelumDikabari();
    $sudah = bookingBelumDikabari();

    WhatsAppMessage::factory()->sent()->create([
        'booking_id' => $sudah->id,
        'template_key' => WhatsAppTemplateKey::BookingConfirmed,
    ]);

    $this->actingAs($this->advisor)
        ->get(route('admin.bookings.index', ['wa' => 'belum']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('filters.wa', 'belum')
            ->has('bookings.data', 1)
            ->where('bookings.data.0.booking_code', $belum->booking_code),
        );
});

it('mengabaikan nilai saringan wa yang tidak dikenali', function () {
    bookingBelumDikabari();

    $this->actingAs($this->advisor)
        ->get(route('admin.bookings.index', ['wa' => 'sudah']))
        ->assertSessionHasErrors('wa');
});
