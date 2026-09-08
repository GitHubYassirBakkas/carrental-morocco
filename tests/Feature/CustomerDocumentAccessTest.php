<?php

use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

function documentAccessUser(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'role' => 'user',
        'is_banned' => false,
    ], $attributes));
}

function documentAccessProfile(User $user, array $overrides = []): CustomerProfile
{
    $paths = [
        'driving_license_front_path' => "private/customer-documents/{$user->id}/driving-license/front/front.jpg",
        'driving_license_back_path' => "private/customer-documents/{$user->id}/driving-license/back/back.jpg",
        'identity_front_path' => "private/customer-documents/{$user->id}/identity/front/front.jpg",
        'identity_back_path' => "private/customer-documents/{$user->id}/identity/back/back.jpg",
    ];

    foreach ($paths as $path) {
        Storage::disk('local')->put($path, 'private document bytes');
    }

    return CustomerProfile::create(array_merge([
        'user_id' => $user->id,
        'date_of_birth' => now()->subYears(30)->toDateString(),
        'driving_license_number' => 'DOC-12345',
        'driving_license_country' => 'Morocco',
        'driving_license_issue_date' => now()->subYears(5)->toDateString(),
        'driving_license_expiry_date' => now()->addYears(5)->toDateString(),
    ], $paths, $overrides));
}

test('guest cannot view admin customer documents', function () {
    Storage::fake('local');

    $profile = documentAccessProfile(documentAccessUser());

    $this->get(route('admin.customer-profiles.documents.show', [$profile, 'driving-license-front']))
        ->assertRedirect(route('login'));
});

test('normal user cannot view admin customer document route', function () {
    Storage::fake('local');

    $profile = documentAccessProfile(documentAccessUser());
    $viewer = documentAccessUser();

    $this->actingAs($viewer)
        ->get(route('admin.customer-profiles.documents.show', [$profile, 'driving-license-front']))
        ->assertForbidden();
});

test('another customer cannot access private documents through route guessing', function () {
    Storage::fake('local');

    $profile = documentAccessProfile(documentAccessUser());
    $otherCustomer = documentAccessUser();

    $this->actingAs($otherCustomer)
        ->get('/admin/customer-profiles/'.$profile->id.'/documents/identity-front')
        ->assertForbidden();
});

test('admin can view each customer document', function (string $document) {
    Storage::fake('local');

    $admin = documentAccessUser(['role' => 'admin']);
    $profile = documentAccessProfile(documentAccessUser());

    $this->actingAs($admin)
        ->get(route('admin.customer-profiles.documents.show', [$profile, $document]))
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff');
})->with([
    'driving-license-front',
    'driving-license-back',
    'identity-front',
    'identity-back',
]);

test('missing customer document returns safe not found response', function () {
    Storage::fake('local');

    $admin = documentAccessUser(['role' => 'admin']);
    $profile = documentAccessProfile(documentAccessUser(), [
        'driving_license_front_path' => 'private/customer-documents/missing/front.jpg',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.customer-profiles.documents.show', [$profile, 'driving-license-front']))
        ->assertNotFound();
});

test('traversal-looking customer document path returns safe not found response', function () {
    Storage::fake('local');

    $admin = documentAccessUser(['role' => 'admin']);
    $profile = documentAccessProfile(documentAccessUser(), [
        'driving_license_front_path' => '../private/customer-documents/escape.jpg',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.customer-profiles.documents.show', [$profile, 'driving-license-front']))
        ->assertNotFound();
});
