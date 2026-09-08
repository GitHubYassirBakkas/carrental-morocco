<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

function phase55User(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'role' => 'user',
        'is_banned' => false,
    ], $attributes));
}

function phase55UserUpdatePayload(User $user, array $overrides = []): array
{
    return array_merge([
        'name' => $user->name,
        'email' => $user->email,
        'phone' => $user->phone,
        'role' => $user->role,
    ], $overrides);
}

function phase55RegistrationPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Phase 5.5 Registrant',
        'email' => 'phase55-registrant@example.test',
        'phone' => '+212600000055',
        'address' => '55 Security Street',
        'city' => 'Rabat',
        'country' => 'Morocco',
        'postal_code' => '10000',
        'date_of_birth' => now()->subYears(35)->toDateString(),
        'driving_license_number' => 'PHASE55-DL',
        'driving_license_country' => 'Morocco',
        'driving_license_issue_date' => now()->subYears(8)->toDateString(),
        'driving_license_expiry_date' => now()->addYears(2)->toDateString(),
        'driving_license_front' => UploadedFile::fake()->image('license-front.jpg'),
        'driving_license_back' => UploadedFile::fake()->image('license-back.jpg'),
        'identity_front' => UploadedFile::fake()->image('identity-front.jpg'),
        'identity_back' => UploadedFile::fake()->image('identity-back.jpg'),
        'password' => 'password',
        'password_confirmation' => 'password',
    ], $overrides);
}

test('registration payload cannot mass assign user security fields', function () {
    Storage::fake('local');

    $response = $this->post('/register', phase55RegistrationPayload([
        'role' => 'admin',
        'is_banned' => true,
        'email_verified_at' => now()->toDateTimeString(),
        'is_admin' => 1,
    ]));

    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'phase55-registrant@example.test')->firstOrFail();

    expect($user->role)->toBe('user')
        ->and((bool) $user->is_banned)->toBeFalse()
        ->and($user->email_verified_at)->toBeNull()
        ->and(Hash::check('password', $user->password))->toBeTrue()
        ->and($user->password)->not->toBe('password');
});

test('profile update cannot mass assign role ban verified state or password', function () {
    $user = phase55User([
        'email_verified_at' => now()->subDay(),
        'password' => Hash::make('password'),
    ]);

    $originalVerifiedAt = $user->email_verified_at?->toDateTimeString();
    $originalPassword = $user->password;

    $response = $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Updated Customer Name',
        'email' => $user->email,
        'phone' => '+212600000055',
        'role' => 'admin',
        'is_banned' => true,
        'email_verified_at' => now()->toDateTimeString(),
        'password' => 'hacked-password',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Updated Customer Name')
        ->and($user->role)->toBe('user')
        ->and((bool) $user->is_banned)->toBeFalse()
        ->and($user->email_verified_at?->toDateTimeString())->toBe($originalVerifiedAt)
        ->and($user->password)->toBe($originalPassword);
});

test('user fill and create do not mass assign sensitive authorization fields', function () {
    $user = phase55User([
        'email_verified_at' => null,
    ]);

    $user->fill([
        'role' => 'admin',
        'is_banned' => true,
        'email_verified_at' => now(),
    ]);

    expect($user->role)->toBe('user')
        ->and((bool) $user->is_banned)->toBeFalse()
        ->and($user->email_verified_at)->toBeNull();

    $created = User::create([
        'name' => 'Mass Assignment Probe',
        'email' => 'phase55-mass-assignment@example.test',
        'password' => 'password',
        'role' => 'admin',
        'is_banned' => true,
        'email_verified_at' => now(),
    ]);

    $created->refresh();

    expect($created->role)->toBe('user')
        ->and((bool) $created->is_banned)->toBeFalse()
        ->and($created->email_verified_at)->toBeNull()
        ->and(Hash::check('password', $created->password))->toBeTrue()
        ->and($created->password)->not->toBe('password');
});

test('admin can still change another users role through trusted explicit assignment', function () {
    $admin = phase55User(['role' => 'admin']);
    $target = phase55User(['role' => 'user']);

    $response = $this->actingAs($admin)
        ->put(route('admin.users.update', $target), phase55UserUpdatePayload($target, [
            'role' => 'admin',
        ]));

    $response->assertRedirect(route('admin.users.index'));
    $response->assertSessionHas('success');
    expect($target->fresh()->role)->toBe('admin');
});

test('admin ban and unban still use trusted explicit assignment', function () {
    $admin = phase55User(['role' => 'admin']);
    $target = phase55User(['role' => 'user']);

    $this->actingAs($admin)
        ->post(route('admin.users.ban', $target))
        ->assertSessionHas('success');

    expect((bool) $target->fresh()->is_banned)->toBeTrue();

    $this->actingAs($admin)
        ->post(route('admin.users.unban', $target))
        ->assertSessionHas('success');

    expect((bool) $target->fresh()->is_banned)->toBeFalse();
});

test('phase 5.4 role protections remain after fillable hardening', function () {
    $admin = phase55User(['role' => 'admin']);
    $target = phase55User(['role' => 'user']);

    $this->actingAs($admin)
        ->from(route('admin.users.edit', $admin))
        ->put(route('admin.users.update', $admin), phase55UserUpdatePayload($admin, [
            'role' => 'user',
        ]))
        ->assertSessionHasErrors('role');

    expect($admin->fresh()->role)->toBe('admin');

    $this->actingAs($admin)
        ->from(route('admin.users.edit', $target))
        ->put(route('admin.users.update', $target), phase55UserUpdatePayload($target, [
            'role' => 'superadmin',
        ]))
        ->assertSessionHasErrors('role');

    expect($target->fresh()->role)->toBe('user');
});

test('intended password update flow stores a hashed password', function () {
    $user = phase55User([
        'password' => Hash::make('password'),
    ]);

    $response = $this->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect(Hash::check('new-password', $user->password))->toBeTrue()
        ->and($user->password)->not->toBe('new-password');
});

test('admin password update stores a hashed password through explicit trusted assignment', function () {
    $admin = phase55User(['role' => 'admin']);
    $target = phase55User(['role' => 'user']);

    $this->actingAs($admin)
        ->put(route('admin.users.update', $target), phase55UserUpdatePayload($target, [
            'password' => 'admin-reset-password',
            'password_confirmation' => 'admin-reset-password',
        ]))
        ->assertSessionHas('success');

    $target->refresh();

    expect(Hash::check('admin-reset-password', $target->password))->toBeTrue()
        ->and($target->password)->not->toBe('admin-reset-password');
});
