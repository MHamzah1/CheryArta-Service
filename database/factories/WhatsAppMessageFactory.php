<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\WhatsAppMessageStatus;
use App\Enums\WhatsAppTemplateKey;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WhatsAppMessage>
 */
class WhatsAppMessageFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'template_key' => WhatsAppTemplateKey::BookingConfirmed,
            'recipient_phone' => '628'.fake()->numerify('##########'),
            'rendered_message' => 'Halo, booking Anda dikonfirmasi. - Chery Arta',
            'generated_by' => User::factory()->serviceAdvisor(),
            'status' => WhatsAppMessageStatus::Generated,
        ];
    }

    public function key(WhatsAppTemplateKey $key): static
    {
        return $this->state(fn (array $attributes): array => ['template_key' => $key]);
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => WhatsAppMessageStatus::Sent,
            'sent_at' => now(),
        ]);
    }

    public function skipped(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => WhatsAppMessageStatus::Skipped,
        ]);
    }
}
