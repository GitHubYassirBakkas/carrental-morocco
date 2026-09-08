<?php

namespace App\ViewModels;

final readonly class BookingViewModel
{
    public function __construct(
        public int $id,
        public string $reference,
        public string $status,
        public string $carBrand,
        public string $carModel,
        public string $carImageUrl,
        public string $startDate,
        public string $endDate,
        public float $totalAmount,
        public ?string $refundStatus,
        public ?string $reviewStatus,
        public ?string $pickupLocationName,
        public ?string $dropoffLocationName
    ) {}
}
