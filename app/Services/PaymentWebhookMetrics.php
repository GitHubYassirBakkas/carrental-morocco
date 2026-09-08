<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class PaymentWebhookMetrics
{
    public function increment(string $metric, array $context = []): void
    {
        Log::info('payment.webhook.metric', array_merge($context, [
            'metric' => $metric,
            'value' => 1,
        ]));
    }

    public function success(array $context = []): void
    {
        $this->increment('webhook_success_count', $context);
    }

    public function ingested(array $context = []): void
    {
        $this->increment('webhook_ingested', $context);
    }

    public function processed(array $context = []): void
    {
        $this->increment('webhook_processed', $context);
    }

    public function fail(array $context = []): void
    {
        $this->increment('webhook_fail_count', $context);
    }

    public function failed(array $context = []): void
    {
        $this->increment('webhook_failed', $context);
    }

    public function retried(array $context = []): void
    {
        $this->increment('webhook_retried', $context);
    }

    public function duplicate(array $context = []): void
    {
        $this->increment('duplicate_event_count', $context);
    }

    public function lockTimeout(array $context = []): void
    {
        $this->increment('lock_timeout_count', $context);
    }
}
