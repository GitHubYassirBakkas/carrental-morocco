<?php

use App\Mail\DriverVerificationApprovedMail;
use App\Mail\DriverVerificationRejectedMail;
use App\Models\CustomerProfile;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

function adminDriverVerificationProfile(User $user, array $attributes = []): CustomerProfile
{
    $paths = [
        'driving_license_front_path' => "private/customer-documents/{$user->id}/driving-license/front/front.jpg",
        'driving_license_back_path' => "private/customer-documents/{$user->id}/driving-license/back/back.jpg",
        'identity_front_path' => "private/customer-documents/{$user->id}/identity/front/front.jpg",
        'identity_back_path' => "private/customer-documents/{$user->id}/identity/back/back.jpg",
    ];

    foreach ($paths as $path) {
        Storage::disk('local')->put($path, 'document');
    }

    $profile = CustomerProfile::create(array_merge([
        'user_id' => $user->id,
        'date_of_birth' => now()->subYears(30)->toDateString(),
        'driving_license_number' => 'ADMIN-DL',
        'driving_license_country' => 'Morocco',
        'driving_license_issue_date' => now()->subYears(5)->toDateString(),
        'driving_license_expiry_date' => now()->addYears(5)->toDateString(),
    ], $paths));

    if ($attributes) {
        $profile->forceFill($attributes)->save();
    }

    return $profile->fresh();
}

test('non admin cannot verify a driver profile', function () {
    Storage::fake('local');

    $profile = adminDriverVerificationProfile(User::factory()->create(), [
        'driver_verification_status' => CustomerProfile::STATUS_PENDING,
    ]);
    $viewer = User::factory()->create(['role' => 'user']);

    $this->actingAs($viewer)
        ->post(route('admin.customer-profiles.driver-verification.verify', $profile))
        ->assertForbidden();

    expect($profile->refresh()->driver_verification_status)->toBe(CustomerProfile::STATUS_PENDING);
});

test('admin cannot verify an incomplete driver profile', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $profile = CustomerProfile::create([
        'user_id' => User::factory()->create()->id,
    ]);

    $this->actingAs($admin)
        ->from(route('admin.users.show', $profile->user))
        ->post(route('admin.customer-profiles.driver-verification.verify', $profile))
        ->assertSessionHasErrors()
        ->assertRedirect(route('admin.users.show', $profile->user));

    expect($profile->refresh()->driver_verification_status)->toBe(CustomerProfile::STATUS_INCOMPLETE)
        ->and($profile->driver_verified_at)->toBeNull()
        ->and($profile->driver_verified_by)->toBeNull();
});

test('admin can verify a complete pending driver profile', function () {
    Storage::fake('local');
    Mail::fake();

    $admin = User::factory()->create(['role' => 'admin']);
    $profile = adminDriverVerificationProfile(User::factory()->create(), [
        'driver_verification_status' => CustomerProfile::STATUS_PENDING,
        'driver_verification_submitted_at' => now()->subHour(),
    ]);

    $this->actingAs($admin)
        ->post(route('admin.customer-profiles.driver-verification.verify', $profile))
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $profile->refresh();

    expect($profile->driver_verification_status)->toBe(CustomerProfile::STATUS_VERIFIED)
        ->and($profile->driver_verified_at)->not->toBeNull()
        ->and($profile->driver_verified_by)->toBe($admin->id)
        ->and($profile->driver_verification_rejection_reason)->toBeNull();

    expect(Notification::where('user_id', $profile->user_id)
        ->where('type', 'driver_verification_approved')
        ->where('title', 'Driver profile verified')
        ->exists())->toBeTrue();

    Mail::assertSent(DriverVerificationApprovedMail::class, function (DriverVerificationApprovedMail $mail) {
        $html = $mail->render();
        $expectedSiteName = setting('site_name', 'Car Rental Morocco');

        return str_contains($html, "You can now reserve vehicles with {$expectedSiteName}")
            && str_contains($html, 'Browse Cars')
            && ! str_contains($html, 'private/customer-documents');
    });
});

test('admin can reject a driver profile with a reason', function () {
    Storage::fake('local');
    Mail::fake();

    $admin = User::factory()->create(['role' => 'admin']);
    $profile = adminDriverVerificationProfile(User::factory()->create(), [
        'driver_verification_status' => CustomerProfile::STATUS_PENDING,
        'driver_verified_at' => now(),
        'driver_verified_by' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.customer-profiles.driver-verification.reject', $profile), [
            'driver_verification_rejection_reason' => 'CNIE back is unreadable.',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $profile->refresh();

    expect($profile->driver_verification_status)->toBe(CustomerProfile::STATUS_REJECTED)
        ->and($profile->driver_verification_rejection_reason)->toBe('CNIE back is unreadable.')
        ->and($profile->driver_verified_at)->toBeNull()
        ->and($profile->driver_verified_by)->toBeNull();

    expect(Notification::where('user_id', $profile->user_id)
        ->where('type', 'driver_verification_rejected')
        ->where('title', 'Driver verification needs attention')
        ->where('message', 'like', '%CNIE back is unreadable.%')
        ->exists())->toBeTrue();

    Mail::assertSent(DriverVerificationRejectedMail::class, function (DriverVerificationRejectedMail $mail) {
        $html = $mail->render();

        return str_contains($html, 'Driver verification needs attention')
            && str_contains($html, 'CNIE back is unreadable.')
            && str_contains($html, 'Update Driver Profile')
            && ! str_contains($html, 'private/customer-documents');
    });
});

test('admin rejection requires a reason', function () {
    Storage::fake('local');

    $admin = User::factory()->create(['role' => 'admin']);
    $profile = adminDriverVerificationProfile(User::factory()->create(), [
        'driver_verification_status' => CustomerProfile::STATUS_PENDING,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.customer-profiles.driver-verification.reject', $profile), [
            'driver_verification_rejection_reason' => '',
        ])
        ->assertSessionHasErrors('driver_verification_rejection_reason');

    expect($profile->refresh()->driver_verification_status)->toBe(CustomerProfile::STATUS_PENDING);
});

test('admin verify remains persisted when mail fails', function () {
    Storage::fake('local');

    $admin = User::factory()->create(['role' => 'admin']);
    $profile = adminDriverVerificationProfile(User::factory()->create(), [
        'driver_verification_status' => CustomerProfile::STATUS_PENDING,
    ]);

    Mail::shouldReceive('to')
        ->once()
        ->andThrow(new RuntimeException('Mail transport unavailable'));

    $this->actingAs($admin)
        ->post(route('admin.customer-profiles.driver-verification.verify', $profile))
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($profile->refresh()->driver_verification_status)->toBe(CustomerProfile::STATUS_VERIFIED)
        ->and($profile->driver_verified_by)->toBe($admin->id)
        ->and(Notification::where('user_id', $profile->user_id)
            ->where('type', 'driver_verification_approved')
            ->exists())->toBeTrue();
});

test('admin reject remains persisted when mail fails', function () {
    Storage::fake('local');

    $admin = User::factory()->create(['role' => 'admin']);
    $profile = adminDriverVerificationProfile(User::factory()->create(), [
        'driver_verification_status' => CustomerProfile::STATUS_PENDING,
    ]);

    Mail::shouldReceive('to')
        ->once()
        ->andThrow(new RuntimeException('Mail transport unavailable'));

    $this->actingAs($admin)
        ->post(route('admin.customer-profiles.driver-verification.reject', $profile), [
            'driver_verification_rejection_reason' => 'Document edges are cropped.',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($profile->refresh()->driver_verification_status)->toBe(CustomerProfile::STATUS_REJECTED)
        ->and($profile->driver_verification_rejection_reason)->toBe('Document edges are cropped.')
        ->and(Notification::where('user_id', $profile->user_id)
            ->where('type', 'driver_verification_rejected')
            ->exists())->toBeTrue();
});
