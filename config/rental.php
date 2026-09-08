<?php

return [
    'dropoff_fee' => (float) env('RENTAL_DROPOFF_FEE', 200),
    'fuel_price_per_percent' => (float) env('RENTAL_FUEL_PRICE_PER_PERCENT', 5),
    'late_fee_per_hour' => (float) env('RENTAL_LATE_FEE_PER_HOUR', 50),
    'late_grace_minutes' => (int) env('RENTAL_LATE_GRACE_MINUTES', 60),
    'tax_percentage' => (float) env('RENTAL_TAX_PERCENTAGE', 0),
    'advance_payment_percentage' => (float) env('RENTAL_ADVANCE_PAYMENT_PERCENTAGE', 30),
    'advance_payment_deadline_hours' => (int) env('RENTAL_ADVANCE_PAYMENT_DEADLINE_HOURS', 24),
];
