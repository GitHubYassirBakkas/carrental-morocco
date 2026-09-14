<?php

use App\Models\Car;
use App\Models\Location;
use App\Models\Setting;
use App\Models\User;

function presentationLocation(array $attributes = []): Location
{
    return Location::factory()->create(array_merge([
        'name' => 'Meknes Branch',
        'address' => 'Avenue Mohammed V, Meknes',
        'city' => 'Meknes',
        'country' => 'Morocco',
        'is_active' => true,
    ], $attributes));
}

function presentationCar(array $attributes = []): Car
{
    $location = $attributes['location'] ?? presentationLocation();
    unset($attributes['location']);

    return Car::factory()->create(array_merge([
        'brand' => 'Dacia',
        'model' => 'Sandero',
        'type' => 'Economy',
        'is_available' => true,
        'location_id' => $location->id,
    ], $attributes));
}

function presentationSetSetting(string $key, ?string $value): void
{
    Setting::query()->updateOrCreate(
        ['key' => $key],
        [
            'value' => $value ?? '',
            'type' => 'text',
            'group' => str_starts_with($key, 'social_') ? 'social' : 'general',
            'label' => str($key)->replace('_', ' ')->title()->toString(),
            'description' => null,
            'autoload' => true,
            'is_public' => true,
        ],
    );
}

function presentationBrandSection(string $html): string
{
    preg_match('/<section class="home-brands">.*?<\/section>/s', $html, $matches);

    return $matches[0] ?? '';
}

test('homepage CTA uses decorative Porsche asset without adding Porsche to brand slider', function () {
    $location = presentationLocation();
    presentationCar(['brand' => 'Dacia', 'location' => $location]);

    $html = $this->get(route('home'))->assertOk()->getContent();
    $brands = presentationBrandSection($html);

    expect($html)->toContain('data-decorative-car="porsche"')
        ->and($html)->toContain('images/cta/luxury-porsche.png')
        ->and($brands)->toContain('Dacia')
        ->and($brands)->not->toContain('Porsche')
        ->and(Car::query()->where('brand', 'Porsche')->exists())->toBeFalse();
});

test('footer renders only configured safe social links with external attributes', function () {
    presentationSetSetting('social_instagram_url', 'https://instagram.com/carrentalmorocco');
    presentationSetSetting('social_whatsapp_url', 'https://wa.me/212535520000');
    presentationSetSetting('social_facebook_url', 'https://facebook.com/carrentalmorocco');

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('href="https://instagram.com/carrentalmorocco"', false)
        ->assertSee('href="https://wa.me/212535520000"', false)
        ->assertSee('href="https://facebook.com/carrentalmorocco"', false)
        ->assertSee('target="_blank"', false)
        ->assertSee('rel="noopener noreferrer"', false)
        ->assertDontSee('href="#"', false);
});

test('blank and invalid social settings do not render footer social links', function () {
    presentationSetSetting('social_instagram_url', '');
    presentationSetSetting('social_whatsapp_url', 'javascript:alert(1)');
    presentationSetSetting('social_facebook_url', 'ftp://example.test/facebook');

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('fa-instagram', false)
        ->assertDontSee('javascript:alert', false)
        ->assertDontSee('ftp://example.test/facebook', false)
        ->assertDontSee('href="#"', false);
});

test('admin settings exposes social fields and rejects unsafe social urls', function () {
    presentationSetSetting('social_instagram_url', '');
    presentationSetSetting('social_whatsapp_url', '');
    presentationSetSetting('social_facebook_url', '');

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.settings.index'))
        ->assertOk()
        ->assertSee('Social Media')
        ->assertSee('social_instagram_url', false)
        ->assertSee('social_whatsapp_url', false)
        ->assertSee('social_facebook_url', false);

    $this->actingAs($admin)
        ->put(route('admin.settings.update'), [
            'social_instagram_url' => 'javascript:alert(1)',
            'social_whatsapp_url' => 'https://wa.me/212535520000',
            'social_facebook_url' => 'https://facebook.com/carrentalmorocco',
        ])
        ->assertSessionHasErrors('social_instagram_url');
});

test('legal public routes render expected content', function () {
    presentationLocation([
        'city' => 'Meknes',
        'address' => 'Avenue Mohammed V, Meknes',
        'phone' => '+212 5 35 52 00 00',
        'email' => 'meknes@example.test',
    ]);

    $this->get(route('legal.privacy'))
        ->assertOk()
        ->assertSee('Last updated: September 2026')
        ->assertSee('Driver Verification Documents')
        ->assertSee('admin-only document routes')
        ->assertSee('does not store full card numbers')
        ->assertSee('meknes@example.test')
        ->assertSee('+212 5 35 52 00 00')
        ->assertSee('Avenue Mohammed V, Meknes')
        ->assertDontSee('This page provides public information only and does not replace a signed rental agreement or invoice.');

    $this->get(route('legal.terms'))
        ->assertOk()
        ->assertSee('Last updated: September 2026')
        ->assertSee('Booking Eligibility and Availability')
        ->assertSee('Driver Verification')
        ->assertSee('Payments and Invoices')
        ->assertSee('Pay at Agency / Advance Payment')
        ->assertSee('Security Deposit')
        ->assertSee('Cancellations and Refunds')
        ->assertSee('currently configured cancellation/refund policy')
        ->assertSee('This page provides public information only and does not replace a signed rental agreement or invoice.');

    $this->get(route('legal.notice'))
        ->assertOk()
        ->assertSee('Car Rental Morocco')
        ->assertSee('Avenue Mohammed V, Meknes')
        ->assertSee('No ICE, RC, IF, VAT, CNDP registration, or legal entity number is displayed');
});

test('footer links to real legal pages and keeps active Meknes behavior', function () {
    presentationLocation(['city' => 'Meknes']);
    presentationLocation(['city' => 'Casablanca', 'is_active' => false]);
    presentationCar();

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('href="'.route('legal.privacy').'"', false)
        ->assertSee('href="'.route('legal.terms').'"', false)
        ->assertSee('href="'.route('legal.notice').'"', false)
        ->assertSee('Meknes')
        ->assertDontSee('Casablanca')
        ->assertDontSee('href="#"', false);
});
