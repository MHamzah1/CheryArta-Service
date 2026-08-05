<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Enums\WhatsAppMessageStatus;
use App\Enums\WhatsAppTemplateKey;
use App\Models\Booking;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppTemplate;

/*
|--------------------------------------------------------------------------
| A7 — Panel draft & jejak pengiriman (docs/08 §8.2, roadmap 2.3.2–2.3.3)
|--------------------------------------------------------------------------
|
| Aturan yang paling mudah rusak diam-diam ada dua, dan keduanya diuji di
| sini: baris log hanya lahir ketika advisor menekan tombolnya (bukan setiap
| perubahan status), dan isi pesannya disusun ULANG di server (bukan diterima
| dari request).
|
*/

beforeEach(function () {
    bekukanWaktuUji();

    $this->advisor = serviceAdvisor();

    $this->template = WhatsAppTemplate::factory()
        ->key(WhatsAppTemplateKey::BookingConfirmed)
        ->create(['body' => 'Halo {{nama}}, booking {{kode_booking}} dikonfirmasi. - Chery Arta']);
});

afterEach(fn () => cairkanWaktuUji());

/**
 * Status dan tanggal ditentukan SAAT PEMBUATAN, bukan lewat `update()`:
 * `status` sengaja tidak fillable (ditentukan server), sehingga `update()`
 * mengabaikannya tanpa suara — dan uji yang menyangka statusnya berubah akan
 * lulus atau gagal karena alasan yang salah.
 */
function bookingDikonfirmasi(array $atribut = [], array $pemilikAtribut = []): Booking
{
    $pemilik = User::factory()->create(['phone_wa' => '6281234567890', ...$pemilikAtribut]);

    return Booking::factory()->create([
        'user_id' => $pemilik->id,
        'status' => BookingStatus::Confirmed,
        ...$atribut,
    ]);
}

it('menampilkan draft berisi teks dari template yang berlaku', function () {
    $booking = bookingDikonfirmasi();

    $this->actingAs($this->advisor)
        ->get(route('admin.bookings.show', $booking))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('whatsapp.template_key', 'booking_confirmed')
            ->where('whatsapp.draft.message', "Halo {$booking->user->name}, booking {$booking->booking_code} dikonfirmasi. - Chery Arta")
            ->where('whatsapp.awaiting', true)
            ->where('whatsapp.reason', null),
        );
});

it('tidak menulis baris log hanya karena halaman dibuka', function () {
    // Inti keputusan grill Q1. Bila ini rusak, kartu dashboard akan penuh
    // draft yang tidak pernah dimaksudkan siapa pun.
    $booking = bookingDikonfirmasi();

    $this->actingAs($this->advisor)->get(route('admin.bookings.show', $booking));

    expect(WhatsAppMessage::query()->count())->toBe(0);
});

it('tidak menulis baris log saat status booking berubah', function () {
    $booking = Booking::factory()->create(['status' => BookingStatus::Pending]);

    $this->actingAs($this->advisor)
        ->put(route('admin.bookings.status', $booking), ['status' => BookingStatus::Confirmed->value]);

    expect(WhatsAppMessage::query()->count())->toBe(0);
});

it('menulis baris log ketika advisor menekan buka WhatsApp', function () {
    $booking = bookingDikonfirmasi();

    $this->actingAs($this->advisor)
        ->post(route('admin.bookings.whatsapp.store', $booking), [
            'template_key' => WhatsAppTemplateKey::BookingConfirmed->value,
        ])
        ->assertRedirect();

    $pesan = WhatsAppMessage::query()->sole();

    expect($pesan->status)->toBe(WhatsAppMessageStatus::Generated)
        ->and($pesan->generated_by)->toBe($this->advisor->id)
        ->and($pesan->recipient_phone)->toBe('6281234567890')
        ->and($pesan->rendered_message)->toContain($booking->booking_code);
});

it('menyusun ulang isi pesan di server dan mengabaikan teks kiriman klien', function () {
    // Kolom audit tidak boleh menjadi isian pengguna (.claude/rules/50).
    $booking = bookingDikonfirmasi();

    $this->actingAs($this->advisor)
        ->post(route('admin.bookings.whatsapp.store', $booking), [
            'template_key' => WhatsAppTemplateKey::BookingConfirmed->value,
            'rendered_message' => 'Pesan palsu yang diketik penyerang.',
        ]);

    expect(WhatsAppMessage::query()->sole()->rendered_message)
        ->not->toContain('palsu')
        ->toContain('dikonfirmasi');
});

it('menandai pesan terkirim beserta waktunya', function () {
    $booking = bookingDikonfirmasi();
    $pesan = WhatsAppMessage::factory()->create(['booking_id' => $booking->id]);

    $this->actingAs($this->advisor)
        ->put(route('admin.bookings.whatsapp.sent', [$booking, $pesan]))
        ->assertRedirect();

    $pesan->refresh();

    expect($pesan->status)->toBe(WhatsAppMessageStatus::Sent)
        ->and($pesan->sent_at)->not->toBeNull();
});

it('menandai pesan dilewati tanpa mengisi waktu kirim', function () {
    $booking = bookingDikonfirmasi();
    $pesan = WhatsAppMessage::factory()->create(['booking_id' => $booking->id]);

    $this->actingAs($this->advisor)->put(route('admin.bookings.whatsapp.skip', [$booking, $pesan]));

    $pesan->refresh();

    expect($pesan->status)->toBe(WhatsAppMessageStatus::Skipped)
        ->and($pesan->sent_at)->toBeNull();
});

it('menolak menandai ulang pesan yang sudah selesai diurus', function () {
    // Dua advisor pada booking yang sama: yang kedua harus melihat penolakan,
    // bukan diam-diam menimpa jejak yang pertama.
    $booking = bookingDikonfirmasi();
    $pesan = WhatsAppMessage::factory()->sent()->create(['booking_id' => $booking->id]);

    $this->actingAs($this->advisor)
        ->from(route('admin.bookings.show', $booking))
        ->put(route('admin.bookings.whatsapp.skip', [$booking, $pesan]))
        ->assertRedirect(route('admin.bookings.show', $booking))
        ->assertSessionHas('error');

    expect($pesan->refresh()->status)->toBe(WhatsAppMessageStatus::Sent);
});

it('menolak menandai pesan milik booking lain', function () {
    $booking = bookingDikonfirmasi();
    $pesanOrangLain = WhatsAppMessage::factory()->create();

    $this->actingAs($this->advisor)
        ->put(route('admin.bookings.whatsapp.sent', [$booking, $pesanOrangLain]))
        ->assertNotFound();
});

it('tidak menawarkan pesan bila pelanggan tidak punya nomor WhatsApp', function () {
    $booking = bookingDikonfirmasi(pemilikAtribut: ['phone_wa' => null]);

    $this->actingAs($this->advisor)
        ->get(route('admin.bookings.show', $booking))
        ->assertInertia(fn ($page) => $page
            ->where('whatsapp.draft', null)
            ->where('whatsapp.reason', 'tanpa_nomor')
            ->where('whatsapp.awaiting', false),
        );
});

it('tidak menawarkan pesan bila templatenya dinonaktifkan', function () {
    // Menonaktifkan harus benar-benar berakibat. Jalur cadangan diam-diam ke
    // teks bawaan akan membuat tombol nonaktif tidak melakukan apa-apa
    // (keputusan grill Q5).
    $this->template->update(['is_active' => false]);
    $booking = bookingDikonfirmasi();

    $this->actingAs($this->advisor)
        ->get(route('admin.bookings.show', $booking))
        ->assertInertia(fn ($page) => $page
            ->where('whatsapp.draft', null)
            ->where('whatsapp.reason', 'template_nonaktif')
            ->where('whatsapp.awaiting', false),
        );
});

it('menolak mencatat pesan untuk template yang dinonaktifkan', function () {
    $this->template->update(['is_active' => false]);
    $booking = bookingDikonfirmasi();

    $this->actingAs($this->advisor)
        ->from(route('admin.bookings.show', $booking))
        ->post(route('admin.bookings.whatsapp.store', $booking), [
            'template_key' => WhatsAppTemplateKey::BookingConfirmed->value,
        ])
        ->assertSessionHas('error');

    expect(WhatsAppMessage::query()->count())->toBe(0);
});

it('mematikan penanda belum dikabari setelah pesan ditandai', function () {
    $booking = bookingDikonfirmasi();

    WhatsAppMessage::factory()->sent()->create([
        'booking_id' => $booking->id,
        'template_key' => WhatsAppTemplateKey::BookingConfirmed,
    ]);

    $this->actingAs($this->advisor)
        ->get(route('admin.bookings.show', $booking))
        ->assertInertia(fn ($page) => $page->where('whatsapp.awaiting', false));
});

it('menawarkan pengingat hanya untuk booking dikonfirmasi yang jadwalnya di depan', function () {
    WhatsAppTemplate::factory()->key(WhatsAppTemplateKey::BookingReminder)->create();

    $mendatang = bookingDikonfirmasi(['booking_date' => UJI_BESOK]);

    $this->actingAs($this->advisor)
        ->get(route('admin.bookings.show', $mendatang))
        ->assertInertia(fn ($page) => $page->where('whatsapp.reminder.template_key', 'booking_reminder'));
});

it('tidak menawarkan pengingat untuk booking yang belum dikonfirmasi', function () {
    WhatsAppTemplate::factory()->key(WhatsAppTemplateKey::BookingReminder)->create();

    $booking = bookingDikonfirmasi(['status' => BookingStatus::Pending, 'booking_date' => UJI_BESOK]);

    $this->actingAs($this->advisor)
        ->get(route('admin.bookings.show', $booking))
        ->assertInertia(fn ($page) => $page->where('whatsapp.reminder', null));
});

it('tidak menawarkan pengingat untuk jadwal yang sudah lewat', function () {
    WhatsAppTemplate::factory()->key(WhatsAppTemplateKey::BookingReminder)->create();

    $booking = bookingDikonfirmasi(['booking_date' => UJI_HARI_INI]);

    $this->actingAs($this->advisor)
        ->get(route('admin.bookings.show', $booking))
        ->assertInertia(fn ($page) => $page->where('whatsapp.reminder', null));
});

it('menyusun tautan wa.me memakai nomor ternormalisasi dan rawurlencode', function () {
    // Cacat sistem lama: nomor disimpan apa adanya lengkap dengan tanda
    // hubung, dan format itu tidak bisa dipakai wa.me sama sekali.
    $booking = bookingDikonfirmasi();
    $booking->user->update(['phone_wa' => '0812-3456-7890']);

    $this->actingAs($this->advisor)
        ->get(route('admin.bookings.show', $booking))
        ->assertInertia(function ($page) {
            $url = $page->toArray()['props']['whatsapp']['draft']['url'];

            expect($url)->toStartWith('https://wa.me/6281234567890?text=')
                ->and($url)->not->toContain('+');
        });
});
