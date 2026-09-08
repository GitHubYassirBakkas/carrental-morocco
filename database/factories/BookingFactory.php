<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Car;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        $location = Location::factory();

        return [
            'user_id' => User::factory(),
            'car_id' => Car::factory(),
            'insurance_id' => null,
            'pickup_location_id' => $location,
            'dropoff_location_id' => $location,
            'start_date' => now()->addDays(3),
            'end_date' => now()->addDays(5),
            'rental_price_per_day' => 500,
            'insurance_fixed_price' => 0,
            'total_amount' => 1000,
            'status' => Booking::STATUS_PENDING,
            'special_requests' => null,
            'pickup_actual' => null,
            'return_actual' => null,
            'initial_mileage' => null,
            'return_mileage' => null,
            'notes' => null,
            'invoiced_at' => null,
            'driver_name' => null,
            'driver_license_number' => null,
            'additional_driver_name' => null,
            'additional_driver_license' => null,
            'advance_payment_amount' => 0,
            'advance_payment_status' => Booking::ADVANCE_PAYMENT_STATUS_PENDING,
            'advance_payment_paid_at' => null,
            'advance_payment_due_at' => now()->addDay(),
            'pickup_instructions' => null,
            'return_instructions' => null,
            'coupon_id' => null,
            'discount_amount' => 0,
            'completed_at' => null,
            'started_at' => null,
            'fuel_at_pickup_percent' => null,
            'fuel_at_return_percent' => null,
            'fuel_used' => 0,
            'fuel_charge' => 0,
            'late_minutes' => 0,
            'late_fee' => 0,
            'security_deposit_amount' => 0,
            'security_deposit_capturable_amount' => 0,
            'security_deposit_intent_id' => null,
            'rental_payment_intent_id' => null,
            'security_deposit_status' => Booking::SECURITY_DEPOSIT_STATUS_PENDING,
            'security_deposit_released_at' => null,
            'security_deposit_charged_amount' => 0,
            'security_deposit_penalty_amount' => 0,
            'security_deposit_refund_id' => null,
            'security_deposit_captured_by' => null,
            'security_deposit_captured_at' => null,
            'security_deposit_refunded_by' => null,
            'security_deposit_refunded_at' => null,
            'security_deposit_penalty_reason' => null,
            'security_deposit_refund_error_message' => null,
            'security_deposit_processed_by' => null,
        ];
    }
}
