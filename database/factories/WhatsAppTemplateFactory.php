<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\WhatsAppTemplateKey;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WhatsAppTemplate>
 */
class WhatsAppTemplateFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $key = fake()->randomElement(WhatsAppTemplateKey::cases());

        return [
            'key' => $key,
            'name' => $key->label(),
            'body' => 'Halo {{nama}}, booking {{kode_booking}} pada {{tanggal}} pukul {{jam}}. - Chery Arta',
            'is_active' => true,
        ];
    }

    public function key(WhatsAppTemplateKey $key): static
    {
        return $this->state(fn (array $attributes): array => [
            'key' => $key,
            'name' => $key->label(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['is_active' => false]);
    }
}
