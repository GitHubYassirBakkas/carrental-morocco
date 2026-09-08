<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

function validRegistrationPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '+212600000001',
        'address' => '123 Avenue Hassan II',
        'city' => 'Casablanca',
        'country' => 'Morocco',
        'postal_code' => '20000',
        'password' => 'password',
        'password_confirmation' => 'password',
    ], $overrides);
}

test('registration screen can be rendered', function () {
    $this->get('/register')->assertStatus(200);
});

test('registration succeeds without driver documents or licence metadata', function () {
    $response = $this->post('/register', validRegistrationPayload());

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'test@example.com')->firstOrFail();

    expect($user->phone)->toBe('+212600000001')
        ->and($user->address)->toBe('123 Avenue Hassan II')
        ->and($user->city)->toBe('Casablanca')
        ->and($user->country)->toBe('Morocco')
        ->and($user->postal_code)->toBe('20000')
        ->and($user->customerProfile)->toBeNull()
        ->and($user->email_verified_at)->toBeNull();
});

test('registration required account fields are enforced', function (string $field) {
    $payload = validRegistrationPayload([$field => null]);

    $this->post('/register', $payload)->assertSessionHasErrors($field);
})->with([
    'name',
    'email',
    'phone',
    'password',
]);

test('address fields may be omitted during registration', function () {
    $this->post('/register', validRegistrationPayload([
        'address' => null,
        'city' => null,
        'country' => null,
        'postal_code' => null,
    ]))->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'test@example.com')->firstOrFail();

    expect($user->address)->toBeNull()
        ->and($user->city)->toBeNull()
        ->and($user->country)->toBeNull()
        ->and($user->postal_code)->toBeNull();
});

test('registration does not create a verified customer profile', function () {
    $this->post('/register', validRegistrationPayload([
        'date_of_birth' => now()->subYears(30)->toDateString(),
        'driving_license_number' => 'DL-123456',
        'driver_verification_status' => 'verified',
        'driver_verified_at' => now()->toDateTimeString(),
        'driver_verified_by' => 1,
    ]))->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'test@example.com')->firstOrFail();

    expect($user->customerProfile)->toBeNull();
});

test('registration payload cannot mass assign protected user fields', function () {
    $this->post('/register', validRegistrationPayload([
        'role' => 'admin',
        'is_banned' => true,
        'email_verified_at' => now()->toDateTimeString(),
    ]))->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'test@example.com')->firstOrFail();

    expect($user->role)->toBe('user')
        ->and((bool) $user->is_banned)->toBeFalse()
        ->and($user->email_verified_at)->toBeNull()
        ->and(Hash::check('password', $user->password))->toBeTrue();
});
