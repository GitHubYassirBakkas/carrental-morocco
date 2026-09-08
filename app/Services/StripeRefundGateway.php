<?php

namespace App\Services;

use Stripe\Refund;
use Stripe\Stripe;

class StripeRefundGateway
{
    public function create(
        string $paymentIntentId,
        int $amountCents,
        string $idempotencyKey,
        array $metadata = []
    ): Refund {
        $secret = config('services.stripe.secret');

        if (empty($secret)) {
            throw new \RuntimeException(
                'Stripe secret key is not configured.'
            );
        }

        Stripe::setApiKey($secret);

        $params = [
            'payment_intent' => $paymentIntentId,
            'amount' => $amountCents,
        ];

        if (! empty($metadata)) {
            $params['metadata'] = $metadata;
        }

        return Refund::create(
            $params,
            [
                'idempotency_key' => $idempotencyKey,
            ]
        );
    }

    public function retrieve(string $refundId): Refund
    {
        $secret = config('services.stripe.secret');

        if (empty($secret)) {
            throw new \RuntimeException(
                'Stripe secret key is not configured.'
            );
        }

        Stripe::setApiKey($secret);

        return Refund::retrieve($refundId);
    }
}
