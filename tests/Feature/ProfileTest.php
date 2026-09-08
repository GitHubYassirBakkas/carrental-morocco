<?php

use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function profilePayload(User $user, array $overrides = []): array
{
    return array_merge([
        'name' => $user->name,
        'email' => $user->email,
        'phone' => '+212600000777',
        'address' => '777 Profile Avenue',
        'city' => 'Tangier',
        'country' => 'Morocco',
        'postal_code' => '90000',
    ], $overrides);
}

function driverProfilePayload(array $overrides = []): array
{
    return array_merge([
        'date_of_birth' => now()->subYears(31)->toDateString(),
        'driving_license_number' => 'PROFILE-DL',
        'driving_license_country' => 'Morocco',
        'driving_license_issue_date' => now()->subYears(5)->toDateString(),
        'driving_license_expiry_date' => now()->addYears(5)->toDateString(),
        'driving_license_front' => UploadedFile::fake()->image('driving-license-front.jpg'),
        'driving_license_back' => UploadedFile::fake()->image('driving-license-back.jpg'),
        'identity_front' => UploadedFile::fake()->image('identity-front.jpg'),
        'identity_back' => UploadedFile::fake()->image('identity-back.jpg'),
    ], $overrides);
}

function profileWithDocuments(User $user, array $attributes = []): CustomerProfile
{
    $paths = [
        'driving_license_front_path' => "private/customer-documents/{$user->id}/driving-license/front/original-front.jpg",
        'driving_license_back_path' => "private/customer-documents/{$user->id}/driving-license/back/original-back.jpg",
        'identity_front_path' => "private/customer-documents/{$user->id}/identity/front/original-front.jpg",
        'identity_back_path' => "private/customer-documents/{$user->id}/identity/back/original-back.jpg",
    ];

    foreach ($paths as $path) {
        Storage::disk('local')->put($path, 'original private document');
    }

    $profile = CustomerProfile::create(array_merge([
        'user_id' => $user->id,
        'date_of_birth' => now()->subYears(31)->toDateString(),
        'driving_license_number' => 'ORIGINAL-DL',
        'driving_license_country' => 'Morocco',
        'driving_license_issue_date' => now()->subYears(8)->toDateString(),
        'driving_license_expiry_date' => now()->addYears(2)->toDateString(),
    ], $paths));

    if ($attributes) {
        $profile->forceFill($attributes)->save();
    }

    return $profile->fresh();
}

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/profile')
        ->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('customer can update contact fields without changing driver profile', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $profile = profileWithDocuments($user, [
        'driver_verification_status' => CustomerProfile::STATUS_VERIFIED,
        'driver_verified_at' => now(),
        'driver_verified_by' => User::factory()->create(['role' => 'admin'])->id,
    ]);

    $response = $this
        ->actingAs($user)
        ->patch('/profile', profilePayload($user, [
            'name' => 'Updated Customer',
            'driver_verification_status' => CustomerProfile::STATUS_REJECTED,
        ]));

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();
    $profile->refresh();

    expect($user->phone)->toBe('+212600000777')
        ->and($user->address)->toBe('777 Profile Avenue')
        ->and($user->city)->toBe('Tangier')
        ->and($user->country)->toBe('Morocco')
        ->and($user->postal_code)->toBe('90000')
        ->and($profile->driving_license_number)->toBe('ORIGINAL-DL')
        ->and($profile->driver_verification_status)->toBe(CustomerProfile::STATUS_VERIFIED);
});

test('driver profile starts incomplete and partial submission remains incomplete', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.driver.update'), [
            'date_of_birth' => now()->subYears(25)->toDateString(),
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.driver.edit'));

    $profile = $user->fresh()->customerProfile;

    expect($profile)->not->toBeNull()
        ->and($profile->driver_verification_status)->toBe(CustomerProfile::STATUS_INCOMPLETE)
        ->and($profile->hasCompleteDriverProfile())->toBeFalse();
});

test('driver profile routes require verified email', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('profile.driver.edit'))
        ->assertRedirect(route('verification.notice'));
});

test('complete driver profile with required metadata and four documents becomes pending', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.driver.update'), driverProfilePayload())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.driver.edit'));

    $profile = $user->fresh()->customerProfile;

    expect($profile->driver_verification_status)->toBe(CustomerProfile::STATUS_PENDING)
        ->and($profile->driver_verification_submitted_at)->not->toBeNull()
        ->and($profile->driver_verified_at)->toBeNull()
        ->and($profile->driver_verified_by)->toBeNull()
        ->and($profile->hasCompleteDriverProfile())->toBeTrue();

    foreach (CustomerProfile::DOCUMENT_FIELDS as $pathField) {
        Storage::disk('local')->assertExists($profile->{$pathField});
        expect($profile->{$pathField})->toStartWith("private/customer-documents/{$user->id}/");
    }
});

test('customer cannot forge driver verification status', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.driver.update'), driverProfilePayload([
            'driver_verification_status' => CustomerProfile::STATUS_VERIFIED,
            'driver_verified_at' => now()->toDateTimeString(),
            'driver_verified_by' => 1,
        ]))
        ->assertSessionHasNoErrors();

    $profile = $user->fresh()->customerProfile;

    expect($profile->driver_verification_status)->toBe(CustomerProfile::STATUS_PENDING)
        ->and($profile->driver_verified_at)->toBeNull()
        ->and($profile->driver_verified_by)->toBeNull();
});

test('customer cannot modify another customers driver profile', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.driver.update'), driverProfilePayload([
            'user_id' => $other->id,
        ]))
        ->assertSessionHasNoErrors();

    expect($user->fresh()->customerProfile)->not->toBeNull()
        ->and($other->fresh()->customerProfile)->toBeNull();
});

test('invalid driver document uploads are rejected', function (UploadedFile $file) {
    Storage::fake('local');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('profile.driver.edit'))
        ->patch(route('profile.driver.update'), driverProfilePayload([
            'driving_license_front' => $file,
        ]))
        ->assertSessionHasErrors('driving_license_front')
        ->assertRedirect(route('profile.driver.edit'));

    expect($user->fresh()->customerProfile)->toBeNull();
})->with([
    'php executable' => fn () => UploadedFile::fake()->create('shell.php', 1, 'application/x-php'),
    'svg image' => fn () => UploadedFile::fake()->create('vector.svg', 1, 'image/svg+xml'),
    'oversized file' => fn () => UploadedFile::fake()->image('large.jpg')->size(5121),
]);

test('rejected driver profile resubmission becomes pending and clears reason', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    profileWithDocuments($user, [
        'driver_verification_status' => CustomerProfile::STATUS_REJECTED,
        'driver_verification_rejection_reason' => 'Unreadable CNIE.',
    ]);

    $this->actingAs($user)
        ->patch(route('profile.driver.update'), driverProfilePayload([
            'driving_license_front' => UploadedFile::fake()->image('replacement-front.jpg'),
        ]))
        ->assertSessionHasNoErrors();

    $profile = $user->fresh()->customerProfile;

    expect($profile->driver_verification_status)->toBe(CustomerProfile::STATUS_PENDING)
        ->and($profile->driver_verification_rejection_reason)->toBeNull()
        ->and($profile->driver_verified_at)->toBeNull()
        ->and($profile->driver_verified_by)->toBeNull();
});

test('verified profile replacing a document requires re-review', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);
    $profile = profileWithDocuments($user, [
        'driver_verification_status' => CustomerProfile::STATUS_VERIFIED,
        'driver_verified_at' => now(),
        'driver_verified_by' => $admin->id,
    ]);
    $oldPath = $profile->driving_license_front_path;

    $this->actingAs($user)
        ->patch(route('profile.driver.update'), [
            'driving_license_front' => UploadedFile::fake()->image('new-license-front.jpg'),
        ])
        ->assertSessionHasNoErrors();

    $profile->refresh();

    Storage::disk('local')->assertExists($profile->driving_license_front_path);
    Storage::disk('local')->assertMissing($oldPath);

    expect($profile->driver_verification_status)->toBe(CustomerProfile::STATUS_PENDING)
        ->and($profile->driver_verified_at)->toBeNull()
        ->and($profile->driver_verified_by)->toBeNull();
});

test('verified profile changing core driver fields requires re-review', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $admin = User::factory()->create(['role' => 'admin']);
    profileWithDocuments($user, [
        'driver_verification_status' => CustomerProfile::STATUS_VERIFIED,
        'driver_verified_at' => now(),
        'driver_verified_by' => $admin->id,
    ]);

    $this->actingAs($user)
        ->patch(route('profile.driver.update'), [
            'date_of_birth' => now()->subYears(40)->toDateString(),
            'driving_license_number' => 'CHANGED-DL',
        ])
        ->assertSessionHasNoErrors();

    $profile = $user->fresh()->customerProfile;

    expect($profile->driver_verification_status)->toBe(CustomerProfile::STATUS_PENDING)
        ->and($profile->driver_verified_at)->toBeNull()
        ->and($profile->driver_verified_by)->toBeNull();
});

test('profile update cannot mass assign protected user fields', function () {
    $user = User::factory()->create([
        'role' => 'user',
        'is_banned' => false,
        'email_verified_at' => now()->subDay(),
    ]);

    $verifiedAt = $user->email_verified_at?->toDateTimeString();

    $this->actingAs($user)
        ->patch('/profile', [
            'name' => 'Protected Probe',
            'email' => $user->email,
            'role' => 'admin',
            'is_banned' => true,
            'email_verified_at' => now()->toDateTimeString(),
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    expect($user->role)->toBe('user')
        ->and((bool) $user->is_banned)->toBeFalse()
        ->and($user->email_verified_at?->toDateTimeString())->toBe($verifiedAt);
});

test('failed private document replacement leaves previous document intact', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $profile = profileWithDocuments($user);
    $oldPath = $profile->driving_license_front_path;

    $this->actingAs($user)
        ->from(route('profile.driver.edit'))
        ->patch(route('profile.driver.update'), [
            'driving_license_front' => UploadedFile::fake()->create('bad.svg', 1, 'image/svg+xml'),
        ])
        ->assertSessionHasErrors('driving_license_front')
        ->assertRedirect(route('profile.driver.edit'));

    Storage::disk('local')->assertExists($oldPath);
    expect($profile->refresh()->driving_license_front_path)->toBe($oldPath);
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});
