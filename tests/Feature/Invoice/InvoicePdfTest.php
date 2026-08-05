<?php

declare(strict_types=1);

use App\Models\Invoice;
use App\Models\InvoiceItem;

/*
|--------------------------------------------------------------------------
| PDF invoice (roadmap 2.4.3, keputusan grill Q6)
|--------------------------------------------------------------------------
|
| Uji ini benar-benar merender PDF-nya, bukan sekadar memeriksa rute. Itu
| disengaja: kegagalan dompdf hanya muncul saat render, dan tanpa uji ini ia
| baru ketahuan di produksi.
|
| Yang TIDAK dibuktikan di sini: bahwa PDF-nya benar di runtime Railway.
| Ekstensi `gd` tidak terpasang di lingkungan mana pun yang sudah diperiksa,
| jadi templatenya dirancang tanpa gambar raster — tetapi DoD #9 tetap menuntut
| satu kali pembuktian manual setelah deploy.
|
*/

beforeEach(fn () => bekukanWaktuUji());
afterEach(fn () => cairkanWaktuUji());

function invoiceLengkap(): Invoice
{
    $invoice = Invoice::factory()->issued('INV/2026/08/0001')->create();

    InvoiceItem::factory()->jasa('Jasa servis berkala', 1, 450_000)->create(['invoice_id' => $invoice->id]);
    InvoiceItem::factory()->part('Oli mesin 5W-30', 4, 75_000)->create(['invoice_id' => $invoice->id]);

    return $invoice;
}

it('merender PDF invoice untuk staf', function () {
    $invoice = invoiceLengkap();

    $respons = $this->actingAs(serviceAdvisor())->get(route('admin.invoices.pdf', $invoice));

    $respons->assertOk();

    expect($respons->headers->get('content-type'))->toContain('application/pdf')
        ->and($respons->headers->get('content-disposition'))->toContain('Invoice-INV-2026-08-0001.pdf');
});

it('menghasilkan berkas PDF yang benar-benar berisi', function () {
    $isi = $this->actingAs(serviceAdvisor())
        ->get(route('admin.invoices.pdf', invoiceLengkap()))
        ->getContent();

    // %PDF- adalah tanda tangan berkas PDF. Tanpa pemeriksaan ini, respons
    // kosong bertipe application/pdf akan lolos sebagai hijau.
    expect($isi)->toStartWith('%PDF-')
        ->and(strlen($isi))->toBeGreaterThan(1000);
});

it('menolak customer dan tamu di rute PDF panel', function () {
    $invoice = invoiceLengkap();

    $this->get(route('admin.invoices.pdf', $invoice))->assertRedirect(route('login'));

    $this->actingAs(customer())
        ->get(route('admin.invoices.pdf', $invoice))
        ->assertForbidden();
});

it('membatasi unduhan berulang lewat throttle', function () {
    $invoice = invoiceLengkap();
    $advisor = serviceAdvisor();

    // Batasnya 10 per menit — sama dengan export laporan, dengan alasan yang
    // sama: satu berkas memuat nama, plat, dan rincian biaya pelanggan.
    foreach (range(1, 10) as $ke) {
        $this->actingAs($advisor)->get(route('admin.invoices.pdf', $invoice))->assertOk();
    }

    $this->actingAs($advisor)
        ->get(route('admin.invoices.pdf', $invoice))
        ->assertStatus(429);
});
