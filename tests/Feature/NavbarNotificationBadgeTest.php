<?php

use App\Models\Notification;
use App\Models\User;

function navbarBadgeUser(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'role' => 'user',
    ], $attributes));
}

function navbarNotification(User $user, array $attributes = []): Notification
{
    return Notification::create(array_merge([
        'user_id' => $user->id,
        'type' => 'account_update',
        'title' => 'Account update',
        'message' => 'Your account has a new notification.',
        'is_read' => false,
        'read_at' => null,
    ], $attributes));
}

function navbarHtmlFor(mixed $test, ?User $user = null, array $session = []): string
{
    $request = $user ? $test->actingAs($user) : $test;

    return $request->withSession($session)
        ->get(route('home'))
        ->assertOk()
        ->getContent();
}

function navbarAvatarBadgeText(string $html): ?string
{
    preg_match('/<span class="notification-avatar-badge"[^>]*>\s*([^<]+)\s*<\/span>/s', $html, $matches);

    return isset($matches[1]) ? trim($matches[1]) : null;
}

test('guest navbar has no notification badge', function () {
    $html = navbarHtmlFor($this);

    expect($html)->not->toContain('<span class="notification-avatar-badge"')
        ->and($html)->not->toContain('<span class="notification-mobile-badge"');
});

test('authenticated user with zero unread notifications has no badge and no settings link', function () {
    $user = navbarBadgeUser();

    $html = navbarHtmlFor($this, $user);

    expect($html)->not->toContain('<span class="notification-avatar-badge"')
        ->and($html)->not->toContain('<span class="notification-mobile-badge"')
        ->and($html)->toContain('0 Unread')
        ->and($html)->not->toContain('<p class="text-sm font-medium text-white">Settings</p>');
});

test('authenticated user with one unread notification sees badge count one', function () {
    $user = navbarBadgeUser();
    navbarNotification($user);

    $html = navbarHtmlFor($this, $user);

    expect($html)->toContain('<span class="notification-avatar-badge"')
        ->and($html)->toContain('1 unread notifications')
        ->and(navbarAvatarBadgeText($html))->toBe('1')
        ->and($html)->toMatch('/1\s+Unread/');
});

test('authenticated user with five unread notifications sees matching badge and dropdown counts', function () {
    $user = navbarBadgeUser();

    foreach (range(1, 5) as $index) {
        navbarNotification($user, ['title' => 'Notification '.$index]);
    }

    $html = navbarHtmlFor($this, $user);

    expect(navbarAvatarBadgeText($html))->toBe('5')
        ->and($html)->toMatch('/5\s+Unread/')
        ->and($html)->toContain('5 unread notifications');
});

test('authenticated user with ten or more unread notifications renders exact count until ninety nine', function () {
    $user = navbarBadgeUser();

    foreach (range(1, 12) as $index) {
        navbarNotification($user, ['title' => 'Notification '.$index]);
    }

    $html = navbarHtmlFor($this, $user);

    expect(navbarAvatarBadgeText($html))->toBe('12')
        ->and($html)->toMatch('/12\s+Unread/');
});

test('authenticated user with one hundred or more unread notifications sees capped badge', function () {
    $user = navbarBadgeUser();

    foreach (range(1, 101) as $index) {
        navbarNotification($user, ['title' => 'Notification '.$index]);
    }

    $html = navbarHtmlFor($this, $user);

    expect(navbarAvatarBadgeText($html))->toBe('99+')
        ->and($html)->toMatch('/99\+\s+Unread/')
        ->and($html)->toContain('101 unread notifications');
});

test('notifications belonging to another user and read notifications do not affect navbar count', function () {
    $user = navbarBadgeUser();
    $otherUser = navbarBadgeUser();

    navbarNotification($user);
    navbarNotification($user, ['is_read' => true, 'read_at' => now()]);
    navbarNotification($otherUser);
    navbarNotification($otherUser);

    $html = navbarHtmlFor($this, $user);

    expect(navbarAvatarBadgeText($html))->toBe('1')
        ->and($html)->toMatch('/1\s+Unread/')
        ->and($html)->not->toMatch('/3\s+Unread/');
});

test('navbar count decreases after mark as read flow', function () {
    $user = navbarBadgeUser();
    $notification = navbarNotification($user);
    navbarNotification($user, ['title' => 'Second notification']);

    $this->actingAs($user)
        ->from(route('notifications.index'))
        ->patch(route('notifications.read', $notification))
        ->assertRedirect(route('notifications.index'));

    expect($notification->fresh()->is_read)->toBeTrue();

    $html = navbarHtmlFor($this, $user);

    expect(navbarAvatarBadgeText($html))->toBe('1')
        ->and($html)->toMatch('/1\s+Unread/')
        ->and($html)->not->toMatch('/2\s+Unread/');
});

test('admin settings route remains available to admins', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.settings.index'))
        ->assertOk();
});

test('navbar notification badge renders across english french and arabic locales', function (string $locale, string $notificationsLabel, string $unreadLabel, string $accessibleLabel) {
    $user = navbarBadgeUser(['name' => 'Customer With A Very Long Display Name']);
    navbarNotification($user);

    $html = navbarHtmlFor($this, $user, ['locale' => $locale]);

    expect($html)->toContain($notificationsLabel)
        ->and($html)->toContain('1 '.$unreadLabel)
        ->and($html)->toContain($accessibleLabel)
        ->and($html)->toContain('<span class="notification-avatar-badge"');

    if ($locale === 'ar') {
        expect($html)->toContain('dir="rtl"')
            ->and($html)->toContain('[dir="rtl"] .notification-avatar-badge');
    }
})->with([
    ['en', 'Notifications', 'Unread', '1 unread notifications'],
    ['fr', 'Notifications', 'Non lu', '1 notifications non lues'],
    ['ar', 'الإشعارات', 'غير مقروء', '1 إشعارات غير مقروءة'],
]);
