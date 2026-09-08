<?php

namespace App\Services;

use Stripe\PaymentIntent;

class StripePaymentIntentGateway
{
    public function retrieve(string $paymentIntentId): object
    {
        return PaymentIntent::retrieve($paymentIntentId);
    }

    public function create(array $params): object
    {
        return PaymentIntent::create($params);
    }
}
