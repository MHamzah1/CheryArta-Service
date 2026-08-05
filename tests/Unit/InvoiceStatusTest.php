<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;

/*
|--------------------------------------------------------------------------
| State machine invoice (docs/04 §4.3, docs/05 §5.6)
|--------------------------------------------------------------------------
|
| Uji unit, bukan feature: yang diperiksa adalah petanya sendiri, bukan
| rute yang memakainya. Barisnya ditulis lengkap — termasuk yang TIDAK sah —
| supaya perpindahan yang diam-diam ditambahkan kelak langsung terlihat.
|
*/

it('mengizinkan draft diterbitkan atau dibatalkan', function () {
    expect(InvoiceStatus::Draft->canTransitionTo(InvoiceStatus::Issued))->toBeTrue()
        ->and(InvoiceStatus::Draft->canTransitionTo(InvoiceStatus::Void))->toBeTrue()
        ->and(InvoiceStatus::Draft->canTransitionTo(InvoiceStatus::Paid))->toBeFalse();
});

it('mengizinkan invoice terbit ditandai lunas atau dibatalkan', function () {
    expect(InvoiceStatus::Issued->canTransitionTo(InvoiceStatus::Paid))->toBeTrue()
        ->and(InvoiceStatus::Issued->canTransitionTo(InvoiceStatus::Void))->toBeTrue()
        ->and(InvoiceStatus::Issued->canTransitionTo(InvoiceStatus::Draft))->toBeFalse();
});

it('mengizinkan invoice lunas dibatalkan', function () {
    // Keputusan grill Q2. Versi enum ini di F1.0 menutup mati jalur tersebut,
    // sehingga satu klik "Tandai Lunas" yang keliru hanya bisa diperbaiki
    // lewat DBeaver.
    expect(InvoiceStatus::Paid->canTransitionTo(InvoiceStatus::Void))->toBeTrue()
        ->and(InvoiceStatus::Paid->canTransitionTo(InvoiceStatus::Issued))->toBeFalse()
        ->and(InvoiceStatus::Paid->canTransitionTo(InvoiceStatus::Draft))->toBeFalse();
});

it('menjadikan void sebagai keadaan akhir', function () {
    foreach (InvoiceStatus::cases() as $tujuan) {
        expect(InvoiceStatus::Void->canTransitionTo($tujuan))->toBeFalse();
    }
});

it('hanya membolehkan draft disunting', function () {
    expect(InvoiceStatus::Draft->isEditable())->toBeTrue()
        ->and(InvoiceStatus::Issued->isEditable())->toBeFalse()
        ->and(InvoiceStatus::Paid->isEditable())->toBeFalse()
        ->and(InvoiceStatus::Void->isEditable())->toBeFalse();
});

it('menghitung hanya invoice terbit dan lunas sebagai pendapatan', function () {
    expect(InvoiceStatus::revenueValues())->toBe(['issued', 'paid']);
});

it('memberi label berbahasa Indonesia untuk tiap status', function () {
    foreach (InvoiceStatus::cases() as $status) {
        expect($status->label())->not->toBe($status->value);
    }
});
