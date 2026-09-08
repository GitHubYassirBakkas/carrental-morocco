<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'user_id' => User::factory(),
            'amount' => 1000,
            'method' => 'cash',
            'type' => Payment::TYPE_PAYMENT,
            'status' => Payment::STATUS_COMPLETED,
            'transaction_id' => 'factory-'.$this->faker->unique()->uuid(),
            'stripe_refund_id' => null,
            'paid_at' => now(),
            'email_sent_at' => null,
            'notes' => null,
        ];
    }
}
