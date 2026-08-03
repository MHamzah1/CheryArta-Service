<?php

declare(strict_types=1);

use App\Models\ContactMessage;
use App\Models\User;

/*
| Form kontak publik (roadmap 1.6.3).
|
| Form ini terbuka untuk tamu, jadi yang diuji bukan hanya "pesannya masuk"
| melainkan juga apa yang TIDAK boleh diterima dari pengirim.
|
| Penghitung throttle tidak perlu dibersihkan antar-uji: CACHE_STORE=array
| (phpunit.xml) berarti setiap uji memulai aplikasi dengan cache kosong.
*/

/** @return array<string, mixed> */
function pesanKontak(array $timpa = []): array
{
    return array_merge([
        'name' => 'Rina Kartika',
        'email' => 'rina@contoh.test',
        'phone' => '0812-3456-7890',
        'subject' => 'Tanya jadwal servis',
        'message' => 'Apakah bisa servis pada hari Sabtu sore? Terima kasih.',
    ], $timpa);
}

it('menyimpan pesan dari form kontak', function () {
    $this->post(route('public.contact.store'), pesanKontak())
        ->assertRedirect(route('public.contact.show'))
        ->assertSessionHas('success');

    expect(ContactMessage::query()->count())->toBe(1);
    expect(ContactMessage::query()->value('subject'))->toBe('Tanya jadwal servis');
});

it('menormalisasi nomor WhatsApp sebelum disimpan', function () {
    $this->post(route('public.contact.store'), pesanKontak(['phone' => '0812-3456-7890']));

    expect(ContactMessage::query()->value('phone'))->toBe('6281234567890');
});

it('menerima pesan tanpa nomor WhatsApp', function () {
    $this->post(route('public.contact.store'), pesanKontak(['phone' => '']))
        ->assertSessionHasNoErrors();

    expect(ContactMessage::query()->value('phone'))->toBeNull();
});

it('mencatat alamat IP pengirim, bukan menerimanya dari form', function () {
    $this->post(route('public.contact.store'), pesanKontak(['ip_address' => '1.2.3.4']));

    expect(ContactMessage::query()->value('ip_address'))->not->toBe('1.2.3.4');
});

it('mengabaikan penanda sudah dibaca yang dikirim dari form', function () {
    // `is_read` dan `read_by` ditentukan staf lewat layar A10, bukan oleh
    // pengirim pesan (.claude/rules/50-keamanan.md).
    $staf = User::factory()->superAdmin()->create();

    $this->post(route('public.contact.store'), pesanKontak([
        'is_read' => true,
        'read_by' => $staf->id,
    ]));

    $pesan = ContactMessage::query()->firstOrFail();

    expect($pesan->is_read)->toBeFalse();
    expect($pesan->read_by)->toBeNull();
});

it('menolak pesan tanpa nama, email, subjek, atau isi', function () {
    $this->post(route('public.contact.store'), [])
        ->assertSessionHasErrors(['name', 'email', 'subject', 'message']);

    expect(ContactMessage::query()->count())->toBe(0);
});

it('menolak pesan yang terlalu singkat', function () {
    $this->post(route('public.contact.store'), pesanKontak(['message' => 'Halo']))
        ->assertSessionHasErrors('message');
});

it('menolak nomor WhatsApp yang tidak dikenali', function () {
    $this->post(route('public.contact.store'), pesanKontak(['phone' => '12345']))
        ->assertSessionHasErrors('phone');
});

it('membatasi pengiriman menjadi lima per jam', function () {
    foreach (range(1, 5) as $ke) {
        $this->post(route('public.contact.store'), pesanKontak(['subject' => "Pesan {$ke}"]))
            ->assertSessionHasNoErrors();
    }

    $this->post(route('public.contact.store'), pesanKontak(['subject' => 'Pesan keenam']))
        ->assertStatus(429);

    expect(ContactMessage::query()->count())->toBe(5);
});
