<?php

declare(strict_types=1);

use App\Enums\WhatsAppTemplateKey;
use App\Models\WhatsAppTemplate;
use Database\Seeders\WhatsAppTemplateSeeder;

/*
|--------------------------------------------------------------------------
| A7 — Penyunting template WhatsApp (docs/07 §A7, roadmap 2.3.4)
|--------------------------------------------------------------------------
|
| Bukan CRUD: tidak ada tambah dan tidak ada hapus. Yang diuji di sini adalah
| bahwa menyunting berhasil, bahwa placeholder asing ditolak dengan pesan yang
| menyebut namanya, dan bahwa seeder tidak menimpa hasil suntingan.
|
*/

beforeEach(function () {
    $this->sa = superAdmin();

    $this->template = WhatsAppTemplate::factory()
        ->key(WhatsAppTemplateKey::BookingConfirmed)
        ->create(['body' => 'Halo {{nama}}, booking {{kode_booking}} dikonfirmasi.']);
});

it('menyimpan perubahan teks template', function () {
    $this->actingAs($this->sa)
        ->put(route('admin.whatsapp-templates.update', $this->template), [
            'name' => 'Booking Dikonfirmasi',
            'body' => 'Halo {{nama}}, unit {{kendaraan}} ditunggu pada {{tanggal}} pukul {{jam}}.',
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.whatsapp-templates.index'))
        ->assertSessionHas('success');

    expect($this->template->refresh()->body)->toContain('{{kendaraan}}');
});

it('menolak placeholder yang tidak dikenali dan menyebut namanya', function () {
    $this->actingAs($this->sa)
        ->put(route('admin.whatsapp-templates.update', $this->template), [
            'name' => 'Booking Dikonfirmasi',
            'body' => 'Halo {{nama}}, totalnya {{harga}}.',
            'is_active' => true,
        ])
        ->assertSessionHasErrors('body');

    expect(session('errors')->first('body'))->toContain('{{harga}}');
});

it('menolak ringkasan_biaya sampai modul invoice lahir di Tahap 11', function () {
    // Placeholder yang sah tetapi selalu kosong lebih menyesatkan daripada
    // yang ditolak dengan penjelasan (keputusan grill Q4b).
    $this->actingAs($this->sa)
        ->put(route('admin.whatsapp-templates.update', $this->template), [
            'name' => 'Servis Selesai',
            'body' => 'Servis selesai. {{ringkasan_biaya}}',
            'is_active' => true,
        ])
        ->assertSessionHasErrors('body');
});

it('menolak isi pesan yang melewati batas panjang template', function () {
    $batas = config('whatsapp.max_template_body_length');

    $this->actingAs($this->sa)
        ->put(route('admin.whatsapp-templates.update', $this->template), [
            'name' => 'Booking Dikonfirmasi',
            'body' => str_repeat('a', $batas + 1),
            'is_active' => true,
        ])
        ->assertSessionHasErrors('body');
});

it('menyisakan ruang aman antara batas template dan batas pesan wa.me', function () {
    // Bila keduanya disamakan, template sepanjang batas akan menghasilkan
    // pesan terpotong begitu placeholder mengembang — tanpa peringatan apa pun.
    expect(config('whatsapp.max_template_body_length'))
        ->toBeLessThan(config('whatsapp.max_message_length'));
});

it('tidak menyediakan rute tambah maupun hapus template', function () {
    $rute = collect(Route::getRoutes())->map(fn ($r) => $r->getName())->filter();

    expect($rute)->not->toContain('admin.whatsapp-templates.create')
        ->and($rute)->not->toContain('admin.whatsapp-templates.store')
        ->and($rute)->not->toContain('admin.whatsapp-templates.destroy');
});

it('mengisi tujuh template lewat seeder', function () {
    WhatsAppTemplate::query()->delete();

    $this->seed(WhatsAppTemplateSeeder::class);

    expect(WhatsAppTemplate::query()->count())->toBe(count(WhatsAppTemplateKey::cases()))
        ->and(WhatsAppTemplate::query()->where('key', 'booking_no_show')->exists())->toBeTrue()
        ->and(WhatsAppTemplate::query()->where('key', 'booking_reminder')->exists())->toBeTrue();
});

it('tidak menimpa suntingan Super Admin saat seeder dijalankan ulang', function () {
    // Risiko yang tercatat di PRD: `updateOrCreate` akan menghapus hasil kerja
    // Super Admin pada setiap `db:seed`, tanpa jejak.
    $this->seed(WhatsAppTemplateSeeder::class);

    $template = WhatsAppTemplate::query()->where('key', 'booking_completed')->sole();
    $template->update(['body' => 'Kalimat buatan pemilik bengkel. {{nama}}']);

    $this->seed(WhatsAppTemplateSeeder::class);

    expect($template->refresh()->body)->toBe('Kalimat buatan pemilik bengkel. {{nama}}');
});

it('tidak menyisakan teks pesan di config lama', function () {
    // Dua sumber teks yang hidup bersamaan adalah pola cacat B2 sistem lama.
    expect(config('company.wa_messages'))->toBeNull()
        ->and(config('whatsapp.templates'))->toBeNull();
});
