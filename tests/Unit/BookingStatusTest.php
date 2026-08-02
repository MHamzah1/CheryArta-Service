<?php

declare(strict_types=1);

use App\Enums\BookingStatus;

/*
| State machine status booking — docs/05-alur-bisnis.md §5.3.
*/

it('mengizinkan transisi yang sah', function (BookingStatus $dari, BookingStatus $ke) {
    expect($dari->canTransitionTo($ke))->toBeTrue();
})->with([
    'pending ke dikonfirmasi' => [BookingStatus::Pending, BookingStatus::Confirmed],
    'pending ke dibatalkan' => [BookingStatus::Pending, BookingStatus::Cancelled],
    'dikonfirmasi ke dikerjakan' => [BookingStatus::Confirmed, BookingStatus::InProgress],
    'dikonfirmasi ke tidak hadir' => [BookingStatus::Confirmed, BookingStatus::NoShow],
    'dikerjakan ke selesai' => [BookingStatus::InProgress, BookingStatus::Completed],
]);

it('menolak transisi yang tidak sah', function (BookingStatus $dari, BookingStatus $ke) {
    expect($dari->canTransitionTo($ke))->toBeFalse();
})->with([
    'pending langsung selesai' => [BookingStatus::Pending, BookingStatus::Completed],
    'pending langsung dikerjakan' => [BookingStatus::Pending, BookingStatus::InProgress],
    'selesai kembali ke dikerjakan' => [BookingStatus::Completed, BookingStatus::InProgress],
    'dibatalkan kembali ke pending' => [BookingStatus::Cancelled, BookingStatus::Pending],
    'tidak hadir ke selesai' => [BookingStatus::NoShow, BookingStatus::Completed],
]);

it('menandai status akhir sebagai terminal', function () {
    expect(BookingStatus::Completed->isTerminal())->toBeTrue()
        ->and(BookingStatus::Cancelled->isTerminal())->toBeTrue()
        ->and(BookingStatus::NoShow->isTerminal())->toBeTrue()
        ->and(BookingStatus::Pending->isTerminal())->toBeFalse();
});

it('hanya mengizinkan penjadwalan ulang pada status pending dan dikonfirmasi', function () {
    expect(BookingStatus::Pending->isReschedulable())->toBeTrue()
        ->and(BookingStatus::Confirmed->isReschedulable())->toBeTrue()
        ->and(BookingStatus::InProgress->isReschedulable())->toBeFalse()
        ->and(BookingStatus::Completed->isReschedulable())->toBeFalse();
});

it('melepaskan kuota slot untuk booking batal dan tidak hadir', function () {
    // Sistem lama menghitung SEMUA booking termasuk yang batal, sehingga
    // slot bisa "penuh palsu" (docs/05 §5.1).
    expect(BookingStatus::quotaReleasingValues())
        ->toEqualCanonicalizing(['cancelled', 'no_show']);
});

it('memakai label berbahasa Indonesia', function () {
    expect(BookingStatus::Pending->label())->toBe('Menunggu Konfirmasi')
        ->and(BookingStatus::InProgress->label())->toBe('Sedang Dikerjakan');
});
