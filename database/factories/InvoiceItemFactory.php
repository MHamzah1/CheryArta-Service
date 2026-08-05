<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InvoiceItemType;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $qty = fake()->randomElement([0.5, 1, 2, 4]);
        $unitPrice = fake()->randomElement([50_000, 75_000, 150_000, 450_000]);

        return [
            'invoice_id' => Invoice::factory(),
            'type' => InvoiceItemType::Jasa,
            'description' => 'Jasa servis berkala',
            'qty' => $qty,
            'unit_price' => $unitPrice,
            'subtotal' => $qty * $unitPrice,
            'sort_order' => 0,
        ];
    }

    public function jasa(string $description, float $qty, float $unitPrice): static
    {
        return $this->baris(InvoiceItemType::Jasa, $description, $qty, $unitPrice);
    }

    public function part(string $description, float $qty, float $unitPrice): static
    {
        return $this->baris(InvoiceItemType::Part, $description, $qty, $unitPrice);
    }

    private function baris(InvoiceItemType $type, string $description, float $qty, float $unitPrice): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => $type,
            'description' => $description,
            'qty' => $qty,
            'unit_price' => $unitPrice,
            'subtotal' => $qty * $unitPrice,
        ]);
    }
}
