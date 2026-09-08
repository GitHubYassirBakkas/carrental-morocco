<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Location>
 */
class LocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->city().' Agency',
            'address' => $this->faker->streetAddress(),
            'city' => 'Casablanca',
            'country' => 'Morocco',
            'postal_code' => '20000',
            'phone' => '+212600000000',
            'email' => $this->faker->safeEmail(),
            'opening_time' => '08:00:00',
            'closing_time' => '20:00:00',
            'notes' => null,
            'is_active' => true,
            'latitude' => null,
            'longitude' => null,
        ];
    }
}
