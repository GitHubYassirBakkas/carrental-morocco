<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Car>
 */
class CarFactory extends Factory
{
    public function definition(): array
    {
        return [
            'brand' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2024,
            'type' => 'Sedan',
            'transmission' => 'Automatic',
            'fuel_type' => 'Petrol',
            'seats' => 5,
            'doors' => 4,
            'luggage' => 2,
            'mileage' => null,
            'price_per_day' => 500,
            'image' => 'factory-car.jpg',
            'gallery' => null,
            'description' => 'Factory test car',
            'features' => ['Air conditioning'],
            'is_available' => true,
            'location_id' => Location::factory(),
            'minimum_age' => 21,
            'fuel_policy' => null,
            'cancellation_policy' => null,
            'security_deposit_amount' => 0,
            'required_documents' => null,
            'fuel_tank_capacity' => 50,
            'fuel_price_per_liter' => 13,
        ];
    }
}
