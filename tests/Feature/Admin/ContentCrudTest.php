<?php

declare(strict_types=1);

use App\Models\ContactMessage;
use App\Models\Facility;
use App\Models\Faq;
use App\Models\Testimonial;

/*
| A10 — Konten landing page & pesan masuk (docs/07 §A10, roadmap 2.2.1–2.2.2).
|
| Tabelnya sudah terisi sejak Tahap 3 (**R4**); yang diuji di sini adalah layar
| pengelolaannya, termasuk apakah perubahannya benar-benar sampai ke halaman
| publik.
*/

it('menyunting FAQ dan langsung mengubah halaman publik', function () {
    $faq = Faq::factory()->create(['question' => 'Pertanyaan lama', 'is_active' => true]);

    $this->actingAs(superAdmin())
        ->put(route('admin.faqs.update', $faq), [
            'question' => 'Berapa lama servis berkala?',
            'answer' => 'Sekitar 90 menit untuk servis berkala standar.',
            'category' => 'Servis',
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.faqs.index'));

    // Yang membuktikan fiturnya berguna bukan barisnya berubah, melainkan
    // halaman publiknya ikut berubah tanpa deploy ulang.
    $this->get(route('public.faq'))
        ->assertOk()
        ->assertSee('Berapa lama servis berkala?');
});

it('menyembunyikan FAQ nonaktif dari halaman publik tanpa menghapus barisnya', function () {
    $faq = Faq::factory()->create(['question' => 'Pertanyaan disembunyikan', 'is_active' => true]);

    $this->actingAs(superAdmin())->put(route('admin.faqs.update', $faq), [
        'question' => $faq->question,
        'answer' => $faq->answer,
        'category' => $faq->category,
        'is_active' => false,
    ]);

    $this->get(route('public.faq'))->assertOk()->assertDontSee('Pertanyaan disembunyikan');

    expect(Faq::query()->whereKey($faq->getKey())->exists())->toBeTrue();
});

it('menyimpan urutan hasil seret', function () {
    $a = Faq::factory()->create(['sort_order' => 1]);
    $b = Faq::factory()->create(['sort_order' => 2]);
    $c = Faq::factory()->create(['sort_order' => 3]);

    $this->actingAs(superAdmin())
        ->put(route('admin.faqs.reorder'), ['ids' => [$c->id, $a->id, $b->id]])
        ->assertRedirect();

    expect($c->refresh()->sort_order)->toBe(1)
        ->and($a->refresh()->sort_order)->toBe(2)
        ->and($b->refresh()->sort_order)->toBe(3);
});

it('menolak urutan yang memuat id dari tabel lain', function () {
    $faq = Faq::factory()->create();
    $fasilitas = Facility::factory()->create(['id' => 9999]);

    $this->actingAs(superAdmin())
        ->put(route('admin.faqs.reorder'), ['ids' => [$faq->id, $fasilitas->id]])
        ->assertSessionHasErrors('ids.1');
});

it('menyembunyikan testimoni yang tidak diterbitkan dari landing page', function () {
    $testimoni = Testimonial::factory()->create([
        'customer_name' => 'Rahmat Hidayat',
        'is_published' => true,
    ]);

    $this->actingAs(superAdmin())->put(route('admin.testimonials.update', $testimoni), [
        'customer_name' => $testimoni->customer_name,
        'car_model' => $testimoni->car_model,
        'rating' => $testimoni->rating,
        'content' => $testimoni->content,
        'is_published' => false,
    ]);

    $this->get(route('home'))->assertOk()->assertDontSee('Rahmat Hidayat');
});

it('menolak rating testimoni di luar 1 sampai 5', function () {
    $this->actingAs(superAdmin())
        ->post(route('admin.testimonials.store'), [
            'customer_name' => 'Uji Rating',
            'car_model' => null,
            'rating' => 9,
            'content' => 'Isi testimoni.',
            'is_published' => true,
        ])
        ->assertSessionHasErrors('rating');
});

/*
| Pesan masuk
*/

it('menandai pesan terbaca beserta pelakunya', function () {
    $pesan = ContactMessage::factory()->create(['is_read' => false]);
    $sa = superAdmin();

    $this->actingAs($sa)->put(route('admin.contact-messages.read', $pesan))->assertRedirect();

    $pesan->refresh();

    expect($pesan->is_read)->toBeTrue()
        ->and($pesan->read_by)->toBe($sa->id)
        ->and($pesan->read_at)->not->toBeNull();
});

it('menghapus pesan kontak secara permanen', function () {
    // Keputusan grill #4: hapus di sini bukan soft delete. Uji ini yang
    // menjaga agar keputusan itu tidak berubah diam-diam.
    $pesan = ContactMessage::factory()->create();

    $this->actingAs(superAdmin())
        ->delete(route('admin.contact-messages.destroy', $pesan))
        ->assertRedirect(route('admin.contact-messages.index'));

    expect(ContactMessage::query()->whereKey($pesan->getKey())->exists())->toBeFalse();
});

it('menyusun tautan balasan WhatsApp dari nomor ternormalisasi', function () {
    $pesan = ContactMessage::factory()->create(['phone' => '081234567890']);

    $this->actingAs(superAdmin())
        ->get(route('admin.contact-messages.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/konten/pesan-masuk/index')
            ->where('messages.data.0.whatsapp_url', fn (?string $url) => $url !== null
                && str_starts_with($url, 'https://wa.me/6281234567890?text=')));
});

it('tidak menyusun tautan WhatsApp bila pengirim tidak mencantumkan nomor', function () {
    ContactMessage::factory()->create(['phone' => null]);

    $this->actingAs(superAdmin())
        ->get(route('admin.contact-messages.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('messages.data.0.whatsapp_url', null));
});

it('menghitung lencana pesan belum dibaca hanya untuk super admin', function () {
    ContactMessage::factory()->count(3)->create(['is_read' => false]);
    ContactMessage::factory()->create(['is_read' => true]);

    $this->actingAs(superAdmin())
        ->get(route('admin.dashboard'))
        ->assertInertia(fn ($page) => $page->where('unreadContactMessages', 3));

    // Advisor tidak boleh tahu angkanya — layarnya pun tidak boleh ia buka.
    $this->actingAs(serviceAdvisor())
        ->get(route('admin.dashboard'))
        ->assertInertia(fn ($page) => $page->where('unreadContactMessages', null));
});
