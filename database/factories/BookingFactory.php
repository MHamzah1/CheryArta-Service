<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Models\ServicePackage;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        // Besok, bukan hari ini: aturan H-1 membuat booking hari ini tidak sah,
        // sehingga tanggal bawaan factory pun sebaiknya tanggal yang sah.
        $date = $this->tanggalKerjaBerikutnya();

        return [
            'booking_code' => 'CA-'.$date->format('Ymd').'-'.Str::padLeft((string) fake()->unique()->numberBetween(1, 9999), 4, '0'),
            'user_id' => User::factory(),
            'vehicle_id' => Vehicle::factory(),
            'service_package_id' => ServicePackage::factory(),
            'booking_date' => $date->toDateString(),
            'booking_time' => '09:00',
            'status' => BookingStatus::Pending,
            'source' => BookingSource::Web,
            'odometer' => fake()->numberBetween(1000, 90000),
            'complaint' => fake()->sentence(),
        ];
    }

    public function onSlot(string $date, string $time): static
    {
        return $this->state(fn (array $attributes) => [
            'booking_date' => $date,
            'booking_time' => $time,
        ]);
    }

    public function status(BookingStatus $status): static
    {
        return $this->state(fn (array $attributes) => ['status' => $status]);
    }

    public function walkIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => BookingSource::WalkIn,
            'status' => BookingStatus::Confirmed,
        ]);
    }

    /** Melewati hari tutup supaya data bawaan factory selalu tanggal yang sah. */
    private function tanggalKerjaBerikutnya(): CarbonImmutable
    {
        /** @var list<int> $closed */
        $closed = config('booking.closed_weekdays');
        $date = CarbonImmutable::now(config('booking.timezone'))->addDay();

        while (in_array($date->dayOfWeek, $closed, true)) {
            $date = $date->addDay();
        }

        return $date;
    }
}
