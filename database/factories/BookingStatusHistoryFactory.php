<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BookingStatusHistory>
 */
class BookingStatusHistoryFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'from_status' => null,
            'to_status' => BookingStatus::Pending,
            'changed_by' => null,
            'note' => null,
        ];
    }
}
