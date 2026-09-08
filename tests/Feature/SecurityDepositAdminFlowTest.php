<?php

use App\Models\Booking;
use App\Models\Car;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;

test('admin penalty capture captures full security deposit and refunds remainder', function () {
    [$admin, $booking] = createHeldSecurityDepositBookingForAdminFlow(5000);
    $stripe = fakeStripeForAdminSecurityDepositFlow($booking->id, 're_security_deposit_4750');

    ApiRequestor::setHttpClient($stripe);

    $this
        ->actingAs($admin)
        ->post(route('admin.security-deposit.charge', $booking), [
            'penalty_amount' => 250,
            'penalty_reason' => 'Damage',
        ])
        ->assertRedirect();

    $booking->refresh();

    expect($stripe->captureRequests())->toHaveCount(1)
        ->and($stripe->captureRequests()[0]['amount_to_capture'] ?? 500000)->toBe(500000)
        ->and($stripe->refundRequests())->toHaveCount(1)
        ->and($stripe->refundRequests()[0]['amount'])->toBe(475000)
        ->and($booking->security_deposit_status)->toBe('partially_refunded')
        ->and((float) $booking->security_deposit_charged_amount)->toBe(5000.0)
        ->and((float) $booking->security_deposit_refunded_amount)->toBe(4750.0)
        ->and((float) $booking->security_deposit_penalty_amount)->toBe(250.0)
        ->and($booking->security_deposit_refund_id)->toBe('re_security_deposit_4750')
        ->and($booking->security_deposit_captured_by)->toBe($admin->id)
        ->and($booking->security_deposit_refunded_by)->toBe($admin->id)
        ->and($booking->security_deposit_captured_at)->not->toBeNull()
        ->and($booking->security_deposit_refunded_at)->not->toBeNull();
});

test('admin zero penalty capture captures full security deposit and fully refunds it', function () {
    [$admin, $booking] = createHeldSecurityDepositBookingForAdminFlow(5000);
    $stripe = fakeStripeForAdminSecurityDepositFlow($booking->id, 're_security_deposit_5000');

    ApiRequestor::setHttpClient($stripe);

    $this
        ->actingAs($admin)
        ->post(route('admin.security-deposit.charge', $booking), [
            'penalty_amount' => 0,
            'penalty_reason' => null,
        ])
        ->assertRedirect();

    $booking->refresh();

    expect($stripe->captureRequests())->toHaveCount(1)
        ->and($stripe->captureRequests()[0]['amount_to_capture'] ?? 500000)->toBe(500000)
        ->and($stripe->refundRequests())->toHaveCount(1)
        ->and($stripe->refundRequests()[0]['amount'])->toBe(500000)
        ->and($booking->security_deposit_status)->toBe('refunded')
        ->and((float) $booking->security_deposit_charged_amount)->toBe(5000.0)
        ->and((float) $booking->security_deposit_refunded_amount)->toBe(5000.0)
        ->and((float) $booking->security_deposit_penalty_amount)->toBe(0.0)
        ->and($booking->security_deposit_refund_id)->toBe('re_security_deposit_5000')
        ->and($booking->security_deposit_captured_by)->toBe($admin->id)
        ->and($booking->security_deposit_refunded_by)->toBe($admin->id)
        ->and($booking->security_deposit_captured_at)->not->toBeNull()
        ->and($booking->security_deposit_refunded_at)->not->toBeNull();
});

test('admin capture marks refund pending when stripe refund fails and retry succeeds', function () {
    [$admin, $booking] = createHeldSecurityDepositBookingForAdminFlow(5000);
    $stripe = fakeStripeForAdminSecurityDepositFlow($booking->id, 're_security_deposit_retry', refundFailures: 1);

    ApiRequestor::setHttpClient($stripe);

    $this
        ->actingAs($admin)
        ->post(route('admin.security-deposit.charge', $booking), [
            'penalty_amount' => 250,
            'penalty_reason' => 'Damage',
        ])
        ->assertSessionHasErrors('security_deposit');

    $booking->refresh();

    expect($stripe->captureRequests())->toHaveCount(1)
        ->and($stripe->refundRequests())->toHaveCount(1)
        ->and($stripe->refundRequests()[0]['amount'])->toBe(475000)
        ->and($booking->security_deposit_status)->toBe('refund_pending')
        ->and((float) $booking->security_deposit_charged_amount)->toBe(5000.0)
        ->and((float) $booking->security_deposit_refunded_amount)->toBe(0.0)
        ->and((float) $booking->security_deposit_penalty_amount)->toBe(250.0)
        ->and($booking->security_deposit_refund_id)->toBeNull()
        ->and($booking->security_deposit_refund_error_message)->toContain('simulated refund failure');

    $this
        ->actingAs($admin)
        ->post(route('admin.security-deposit.retry-refund', $booking))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $booking->refresh();

    expect($stripe->captureRequests())->toHaveCount(1)
        ->and($stripe->refundRequests())->toHaveCount(2)
        ->and($stripe->refundRequests()[1]['amount'])->toBe(475000)
        ->and($booking->security_deposit_status)->toBe('partially_refunded')
        ->and((float) $booking->security_deposit_refunded_amount)->toBe(4750.0)
        ->and((float) $booking->security_deposit_penalty_amount)->toBe(250.0)
        ->and($booking->security_deposit_refund_id)->toBe('re_security_deposit_retry')
        ->and($booking->security_deposit_refund_error_message)->toBeNull()
        ->and($booking->security_deposit_refunded_by)->toBe($admin->id)
        ->and($booking->security_deposit_refunded_at)->not->toBeNull();
});

test('admin double click capture only captures and refunds once', function () {
    [$admin, $booking] = createHeldSecurityDepositBookingForAdminFlow(5000);
    $stripe = fakeStripeForAdminSecurityDepositFlow($booking->id, 're_security_deposit_double_capture');

    ApiRequestor::setHttpClient($stripe);

    $payload = [
        'penalty_amount' => 250,
        'penalty_reason' => 'Damage',
    ];

    $this->actingAs($admin)->post(route('admin.security-deposit.charge', $booking), $payload)->assertRedirect();
    $this->actingAs($admin)->post(route('admin.security-deposit.charge', $booking->fresh()), $payload)->assertRedirect();

    $booking->refresh();

    expect($stripe->captureRequests())->toHaveCount(1)
        ->and($stripe->refundRequests())->toHaveCount(1)
        ->and($booking->security_deposit_status)->toBe('partially_refunded')
        ->and((float) $booking->security_deposit_refunded_amount)->toBe(4750.0)
        ->and($booking->security_deposit_refund_id)->toBe('re_security_deposit_double_capture');
});

test('admin double click retry refund only creates one refund', function () {
    [$admin, $booking] = createHeldSecurityDepositBookingForAdminFlow(5000);
    $booking->update([
        'security_deposit_status' => 'refund_pending',
        'security_deposit_capturable_amount' => 0,
        'security_deposit_charged_amount' => 5000,
        'security_deposit_refunded_amount' => 0,
        'security_deposit_penalty_amount' => 250,
        'security_deposit_refund_error_message' => 'previous refund failure',
    ]);
    $stripe = fakeStripeForAdminSecurityDepositFlow(
        $booking->id,
        're_security_deposit_double_retry',
        initialCaptured: true
    );

    ApiRequestor::setHttpClient($stripe);

    $this->actingAs($admin)->post(route('admin.security-deposit.retry-refund', $booking))->assertRedirect();
    $this->actingAs($admin)->post(route('admin.security-deposit.retry-refund', $booking->fresh()))->assertRedirect();

    $booking->refresh();

    expect($stripe->captureRequests())->toHaveCount(0)
        ->and($stripe->refundRequests())->toHaveCount(1)
        ->and($stripe->refundRequests()[0]['amount'])->toBe(475000)
        ->and($booking->security_deposit_status)->toBe('partially_refunded')
        ->and((float) $booking->security_deposit_refunded_amount)->toBe(4750.0)
        ->and($booking->security_deposit_refund_id)->toBe('re_security_deposit_double_retry');
});

function createHeldSecurityDepositBookingForAdminFlow(float $securityDepositAmount): array
{
    ensureSecurityDepositAdminFlowTestColumns();

    $admin = User::factory()->create(['role' => 'admin']);
    $customer = User::factory()->create(['role' => 'user']);

    $location = Location::create([
        'name' => 'Casablanca Downtown',
        'address' => '1 Test Street',
        'city' => 'Casablanca',
        'country' => 'Morocco',
        'postal_code' => '20000',
        'phone' => '+212600000000',
        'email' => 'casa@example.com',
        'opening_time' => '08:00',
        'closing_time' => '20:00',
        'is_active' => true,
    ]);

    $car = Car::create([
        'brand' => 'Toyota',
        'model' => 'Corolla',
        'year' => 2024,
        'type' => 'Sedan',
        'transmission' => 'Automatic',
        'fuel_type' => 'Petrol',
        'seats' => 5,
        'doors' => 4,
        'luggage' => 2,
        'price_per_day' => 650,
        'image' => 'cars/test.jpg',
        'is_available' => true,
        'location_id' => $location->id,
        'security_deposit_amount' => $securityDepositAmount,
    ]);

    $booking = Booking::create([
        'user_id' => $customer->id,
        'car_id' => $car->id,
        'pickup_location_id' => $location->id,
        'dropoff_location_id' => $location->id,
        'start_date' => now()->addDay(),
        'end_date' => now()->addDays(3),
        'rental_price_per_day' => 650,
        'insurance_fixed_price' => 0,
        'total_amount' => 1300,
        'status' => 'confirmed',
        'advance_payment_amount' => 1300,
        'advance_payment_status' => 'paid',
        'security_deposit_amount' => $securityDepositAmount,
        'security_deposit_capturable_amount' => $securityDepositAmount,
        'security_deposit_intent_id' => 'pi_security_deposit_admin_flow',
        'security_deposit_status' => 'held',
    ]);

    return [$admin, $booking];
}

function ensureSecurityDepositAdminFlowTestColumns(): void
{
    if (!Schema::hasColumn('bookings', 'security_deposit_refunded_amount')) {
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('security_deposit_refunded_amount', 10, 2)->default(0);
        });
    }

    if (!Schema::hasColumn('bookings', 'security_deposit_refund_error_message')) {
        Schema::table('bookings', function (Blueprint $table) {
            $table->text('security_deposit_refund_error_message')->nullable();
        });
    }

    if (!Schema::hasColumn('bookings', 'security_deposit_processed_by')) {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('security_deposit_processed_by')->nullable();
        });
    }
}

function fakeStripeForAdminSecurityDepositFlow(
    int $bookingId,
    string $refundId,
    int $refundFailures = 0,
    bool $initialCaptured = false
): ClientInterface
{
    return new class($bookingId, $refundId, $refundFailures, $initialCaptured) implements ClientInterface {
        public array $requests = [];
        private bool $captured;
        private int $refundAttempts = 0;

        public function __construct(
            private readonly int $bookingId,
            private readonly string $refundId,
            private readonly int $refundFailures,
            bool $initialCaptured
        ) {
            $this->captured = $initialCaptured;
        }

        public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null): array
        {
            $path = parse_url($absUrl, PHP_URL_PATH) ?: '';
            $this->requests[] = [
                'method' => strtolower($method),
                'path' => $path,
                'params' => $params,
            ];

            if (strtolower($method) === 'get' && $path === '/v1/payment_intents/pi_security_deposit_admin_flow') {
                return $this->json([
                    'id' => 'pi_security_deposit_admin_flow',
                    'object' => 'payment_intent',
                    'status' => $this->captured ? 'succeeded' : 'requires_capture',
                    'amount' => 500000,
                    'amount_capturable' => $this->captured ? 0 : 500000,
                    'amount_received' => $this->captured ? 500000 : 0,
                    'metadata' => [
                        'booking_id' => (string) $this->bookingId,
                        'type' => 'security_deposit',
                    ],
                ]);
            }

            if (strtolower($method) === 'post' && $path === '/v1/payment_intents/pi_security_deposit_admin_flow/capture') {
                $this->captured = true;

                return $this->json([
                    'id' => 'pi_security_deposit_admin_flow',
                    'object' => 'payment_intent',
                    'status' => 'succeeded',
                    'amount' => 500000,
                    'amount_capturable' => 0,
                    'amount_received' => 500000,
                    'metadata' => [
                        'booking_id' => (string) $this->bookingId,
                        'type' => 'security_deposit',
                    ],
                ]);
            }

            if (strtolower($method) === 'post' && $path === '/v1/refunds') {
                $this->refundAttempts++;

                if ($this->refundAttempts <= $this->refundFailures) {
                    return $this->json([
                        'error' => [
                            'type' => 'api_error',
                            'message' => 'simulated refund failure',
                        ],
                    ], 500);
                }

                return $this->json([
                    'id' => $this->refundId,
                    'object' => 'refund',
                    'amount' => $params['amount'] ?? 0,
                    'payment_intent' => $params['payment_intent'] ?? null,
                    'status' => 'succeeded',
                ]);
            }

            return $this->json([
                'error' => [
                    'type' => 'invalid_request_error',
                    'message' => 'Unexpected fake Stripe request: ' . strtoupper($method) . ' ' . $path,
                ],
            ], 400);
        }

        public function captureRequests(): array
        {
            return array_values(array_map(
                fn (array $request): array => $request['params'],
                array_filter(
                    $this->requests,
                    fn (array $request): bool => $request['method'] === 'post'
                        && $request['path'] === '/v1/payment_intents/pi_security_deposit_admin_flow/capture'
                )
            ));
        }

        public function refundRequests(): array
        {
            return array_values(array_map(
                fn (array $request): array => $request['params'],
                array_filter(
                    $this->requests,
                    fn (array $request): bool => $request['method'] === 'post'
                        && $request['path'] === '/v1/refunds'
                )
            ));
        }

        private function json(array $body, int $status = 200): array
        {
            return [json_encode($body, JSON_THROW_ON_ERROR), $status, ['Request-Id' => 'req_security_deposit_admin_flow']];
        }
    };
}
