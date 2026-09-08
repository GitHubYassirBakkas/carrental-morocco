<?php

use App\Models\User;

function phase54User(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'role' => 'user',
        'is_banned' => false,
    ], $attributes));
}

function phase54UserUpdatePayload(User $user, array $overrides = []): array
{
    return array_merge([
        'name' => $user->name,
        'email' => $user->email,
        'phone' => $user->phone,
        'role' => $user->role,
    ], $overrides);
}

test('customer cannot access admin user management', function () {
    $customer = phase54User();
    $target = phase54User();

    $this->actingAs($customer)
        ->get(route('admin.users.index'))
        ->assertForbidden();

    $this->actingAs($customer)
        ->put(route('admin.users.update', $target), phase54UserUpdatePayload($target, [
            'role' => 'admin',
        ]))
        ->assertForbidden();

    $this->actingAs($customer)
        ->post(route('admin.users.ban', $target))
        ->assertForbidden();
});

test('admin cannot self demote even when another active admin exists', function () {
    $admin = phase54User(['role' => 'admin']);
    phase54User(['role' => 'admin']);

    $response = $this->actingAs($admin)
        ->from(route('admin.users.edit', $admin))
        ->put(route('admin.users.update', $admin), phase54UserUpdatePayload($admin, [
            'role' => 'user',
        ]));

    $response->assertRedirect(route('admin.users.edit', $admin));
    $response->assertSessionHasErrors('role');
    expect($admin->fresh()->role)->toBe('admin');
});

test('only active admin cannot lose admin role', function () {
    $admin = phase54User(['role' => 'admin']);

    $response = $this->actingAs($admin)
        ->from(route('admin.users.edit', $admin))
        ->put(route('admin.users.update', $admin), phase54UserUpdatePayload($admin, [
            'role' => 'user',
        ]));

    $response->assertRedirect(route('admin.users.edit', $admin));
    $response->assertSessionHasErrors('role');
    expect($admin->fresh()->role)->toBe('admin');
});

test('only active admin cannot be banned or deleted', function () {
    $admin = phase54User(['role' => 'admin']);

    $this->actingAs($admin)
        ->from(route('admin.users.show', $admin))
        ->post(route('admin.users.ban', $admin))
        ->assertRedirect(route('admin.users.show', $admin))
        ->assertSessionHas('error');

    expect((bool) $admin->fresh()->is_banned)->toBeFalse();

    $this->actingAs($admin)
        ->from(route('admin.users.show', $admin))
        ->delete(route('admin.users.destroy', $admin))
        ->assertRedirect(route('admin.users.show', $admin))
        ->assertSessionHas('error');

    expect(User::whereKey($admin->id)->exists())->toBeTrue();
    expect($admin->fresh()->role)->toBe('admin');
});

test('arbitrary role values are rejected and leave user unchanged', function () {
    $admin = phase54User(['role' => 'admin']);
    $target = phase54User(['role' => 'user']);

    $response = $this->actingAs($admin)
        ->from(route('admin.users.edit', $target))
        ->put(route('admin.users.update', $target), phase54UserUpdatePayload($target, [
            'role' => 'superadmin',
        ]));

    $response->assertRedirect(route('admin.users.edit', $target));
    $response->assertSessionHasErrors('role');
    expect($target->fresh()->role)->toBe('user');
});

test('with two active admins one admin can demote another admin', function () {
    $actor = phase54User(['role' => 'admin']);
    $target = phase54User(['role' => 'admin']);

    $response = $this->actingAs($actor)
        ->put(route('admin.users.update', $target), phase54UserUpdatePayload($target, [
            'role' => 'user',
        ]));

    $response->assertRedirect(route('admin.users.index'));
    $response->assertSessionHas('success');
    expect($target->fresh()->role)->toBe('user');
    expect($actor->fresh()->role)->toBe('admin');
});
