<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BookingInspection>
 */
class BookingInspectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'type' => 'checkin',
            'mileage' => 100,
            'fuel_level' => 80,
            'has_damage' => false,
            'damage_notes' => null,
            'created_by' => User::factory(),
        ];
    }
}
