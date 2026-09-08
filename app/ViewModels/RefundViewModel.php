<?php

namespace App\ViewModels;

final readonly class RefundViewModel
{
    public function __construct(
        public string $refundReference,
        public string $customerName,
        public string $bookingReference,
        public string $carName,
        public float $refundAmount,
        public string $refundMethod,
        public string $refundStatus,
        public string $refundDate,
        public string $refundType,
        public int $paymentId,
        public int $bookingId
    ) {}
}
