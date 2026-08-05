<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Enums\InvoiceStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory()->status(BookingStatus::Completed),
            'invoice_number' => null,
            'subtotal' => 0,
            'discount' => 0,
            'tax' => 0,
            'total' => 0,
            'status' => InvoiceStatus::Draft,
            'created_by' => User::factory()->serviceAdvisor(),
        ];
    }

    /**
     * Angka yang konsisten dengan rumus server: total = subtotal − diskon + pajak.
     *
     * Factory yang menulis subtotal dan total secara terpisah membuat uji
     * berdiri di atas invoice yang mustahil lahir dari InvoiceService.
     */
    public function amounts(float $subtotal, float $discount = 0, float $tax = 0): static
    {
        return $this->state(fn (array $attributes): array => [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'total' => $subtotal - $discount + $tax,
        ]);
    }

    public function issued(?string $number = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => InvoiceStatus::Issued,
            'invoice_number' => $number ?? 'INV/'.now()->format('Y/m').'/'.fake()->unique()->numerify('####'),
            'issued_at' => now(),
        ]);
    }

    public function paid(string $method = 'cash'): static
    {
        return $this->issued()->state(fn (array $attributes): array => [
            'status' => InvoiceStatus::Paid,
            'paid_at' => now(),
            'payment_method' => $method,
        ]);
    }

    /**
     * Pernah terbit lalu dicabut — bentuk yang dipakai menguji keputusan grill
     * Q7: pelanggan tetap boleh melihatnya berlencana "Dibatalkan".
     */
    public function voided(string $reason = 'Salah input harga'): static
    {
        return $this->issued()->state(fn (array $attributes): array => [
            'status' => InvoiceStatus::Void,
            'void_reason' => $reason,
        ]);
    }

    /** Dicabut selagi masih draft — tidak pernah punya nomor, tidak pernah terlihat pelanggan. */
    public function voidedDraft(string $reason = 'Dibatalkan sebelum terbit'): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => InvoiceStatus::Void,
            'void_reason' => $reason,
        ]);
    }
}
