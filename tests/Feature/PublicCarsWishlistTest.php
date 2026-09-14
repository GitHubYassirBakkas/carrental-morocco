<?php

use App\Models\Car;
use App\Models\Location;
use App\Models\User;
use App\Models\Wishlist;

function publicCarsWishlistLocation(array $attributes = []): Location
{
    return Location::factory()->create(array_merge([
        'name' => 'Marrakech Branch',
        'address' => 'Avenue Mohammed VI, Marrakech',
        'city' => 'Marrakech',
        'country' => 'Morocco',
        'is_active' => true,
    ], $attributes));
}

function publicCarsWishlistCar(Location $location, array $attributes = []): Car
{
    return Car::factory()->create(array_merge([
        'brand' => 'Dacia',
        'model' => 'Sandero',
        'type' => 'Economy',
        'location_id' => $location->id,
        'is_available' => true,
    ], $attributes));
}

function publicCarsWishlistFormHtml(string $html, Car $car): string
{
    preg_match(
        '/<form\b(?=[^>]*data-wishlist-car-id="'.preg_quote((string) $car->id, '/').'")[\s\S]*?<\/form>/',
        $html,
        $matches
    );

    return $matches[0] ?? '';
}

test('car listing renders wishlist button for authenticated customers', function () {
    $location = publicCarsWishlistLocation();
    $car = publicCarsWishlistCar($location);
    $user = User::factory()->create();

    $html = $this->actingAs($user)
        ->get(route('cars.index'))
        ->assertOk()
        ->getContent();

    $form = publicCarsWishlistFormHtml($html, $car);

    expect($form)->toContain('action="'.route('wishlist.store', $car).'"')
        ->and($form)->toContain('wishlist-toggle-form--overlay')
        ->and($form)->toContain('type="submit"')
        ->and($form)->toContain('fa-regular fa-heart')
        ->and($form)->not->toContain('fa-solid fa-heart')
        ->and($form)->not->toContain('wishlist-toggle-btn--active')
        ->and($form)->toContain(__('messages.wishlist_add'));
});

test('car listing marks already saved wishlist cars active', function () {
    $location = publicCarsWishlistLocation();
    $savedCar = publicCarsWishlistCar($location, ['model' => 'Saved']);
    $unsavedCar = publicCarsWishlistCar($location, ['model' => 'Unsaved']);
    $user = User::factory()->create();

    Wishlist::create([
        'user_id' => $user->id,
        'car_id' => $savedCar->id,
    ]);

    $html = $this->actingAs($user)
        ->get(route('cars.index'))
        ->assertOk()
        ->getContent();

    $savedForm = publicCarsWishlistFormHtml($html, $savedCar);
    $unsavedForm = publicCarsWishlistFormHtml($html, $unsavedCar);

    expect($savedForm)->toContain('action="'.route('wishlist.destroy', $savedCar).'"')
        ->and($savedForm)->toContain('name="_method" value="DELETE"')
        ->and($savedForm)->toContain('wishlist-toggle-btn--active')
        ->and($savedForm)->toContain('fa-solid fa-heart')
        ->and($savedForm)->not->toContain('fa-regular fa-heart')
        ->and($savedForm)->toContain(__('messages.wishlist_remove'))
        ->and($unsavedForm)->toContain('action="'.route('wishlist.store', $unsavedCar).'"')
        ->and($unsavedForm)->not->toContain('name="_method" value="DELETE"')
        ->and($unsavedForm)->not->toContain('wishlist-toggle-btn--active')
        ->and($unsavedForm)->toContain('fa-regular fa-heart')
        ->and($unsavedForm)->not->toContain('fa-solid fa-heart');
});

test('car listing wishlist toggle uses existing wishlist endpoint and returns to listing', function () {
    $location = publicCarsWishlistLocation();
    $car = publicCarsWishlistCar($location);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('cars.index'))
        ->post(route('wishlist.store', $car))
        ->assertRedirect(route('cars.index'));

    $this->assertDatabaseHas('wishlists', [
        'user_id' => $user->id,
        'car_id' => $car->id,
    ]);

    $this->actingAs($user)
        ->from(route('cars.index'))
        ->delete(route('wishlist.destroy', $car))
        ->assertRedirect(route('cars.index'));

    $this->assertDatabaseMissing('wishlists', [
        'user_id' => $user->id,
        'car_id' => $car->id,
    ]);
});

test('guest car listing keeps the details page wishlist behavior', function () {
    $location = publicCarsWishlistLocation();
    $car = publicCarsWishlistCar($location);

    $html = $this->get(route('cars.index'))
        ->assertOk()
        ->getContent();

    expect(publicCarsWishlistFormHtml($html, $car))->toBe('');
});

test('car listing wishlist button does not replace view details navigation', function () {
    $location = publicCarsWishlistLocation();
    $car = publicCarsWishlistCar($location);
    $user = User::factory()->create();

    $html = $this->actingAs($user)
        ->get(route('cars.index'))
        ->assertOk()
        ->getContent();

    expect(substr_count($html, 'href="'.route('insurance.select', $car).'"'))->toBe(2)
        ->and(publicCarsWishlistFormHtml($html, $car))->not->toContain('<a ');
});

test('car details uses the same wishlist visual states and toggle behavior', function () {
    $location = publicCarsWishlistLocation();
    $car = publicCarsWishlistCar($location);
    $user = User::factory()->create();

    $unsavedHtml = $this->actingAs($user)
        ->get(route('cars.show', $car))
        ->assertOk()
        ->getContent();

    $unsavedForm = publicCarsWishlistFormHtml($unsavedHtml, $car);

    expect($unsavedForm)->toContain('wishlist-toggle-form--inline')
        ->and($unsavedForm)->toContain('action="'.route('wishlist.store', $car).'"')
        ->and($unsavedForm)->toContain('fa-regular fa-heart')
        ->and($unsavedForm)->not->toContain('wishlist-toggle-btn--active')
        ->and($unsavedHtml)->toContain('fetch(form.action')
        ->and($unsavedHtml)->toContain('setWishlistState(form, !isRemoving)');

    Wishlist::create([
        'user_id' => $user->id,
        'car_id' => $car->id,
    ]);

    $savedHtml = $this->actingAs($user)
        ->get(route('cars.show', $car))
        ->assertOk()
        ->getContent();

    $savedForm = publicCarsWishlistFormHtml($savedHtml, $car);

    expect($savedForm)->toContain('action="'.route('wishlist.destroy', $car).'"')
        ->and($savedForm)->toContain('wishlist-toggle-btn--active')
        ->and($savedForm)->toContain('fa-solid fa-heart')
        ->and($savedForm)->not->toContain('fa-regular fa-heart');
});

test('car listing includes the immediate wishlist state switcher', function () {
    $location = publicCarsWishlistLocation();
    publicCarsWishlistCar($location);
    $user = User::factory()->create();

    $html = $this->actingAs($user)
        ->get(route('cars.index'))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('fetch(form.action')
        ->and($html)->toContain("icon.classList.toggle('fa-solid', isActive)")
        ->and($html)->toContain("icon.classList.toggle('fa-regular', !isActive)")
        ->and($html)->toContain('setWishlistState(form, !isRemoving)');
});

test('wishlist active style keeps transparent button and colors only the heart icon', function () {
    $partial = file_get_contents(resource_path('views/partials/wishlist-toggle.blade.php'));

    preg_match('/\.wishlist-toggle-btn\s*\{(?P<base>.*?)\}/s', $partial, $baseMatches);
    preg_match('/\.wishlist-toggle-btn--active\s*\{(?P<active>.*?)\}/s', $partial, $activeMatches);
    preg_match('/\.wishlist-toggle-btn--active:hover,\s*\.wishlist-toggle-btn--active:focus-visible\s*\{(?P<activeHover>.*?)\}/s', $partial, $activeHoverMatches);
    preg_match('/\.wishlist-toggle-btn:hover,\s*\.wishlist-toggle-btn:focus-visible\s*\{(?P<hover>.*?)\}/s', $partial, $hoverMatches);

    $base = $baseMatches['base'] ?? '';
    $active = $activeMatches['active'] ?? '';
    $activeHover = $activeHoverMatches['activeHover'] ?? '';
    $hover = $hoverMatches['hover'] ?? '';

    expect($base)->toContain('background: transparent')
        ->and($base)->toContain('border: 0')
        ->and($base)->toContain('box-shadow: none')
        ->and($active)->toContain('background: transparent')
        ->and($active)->toContain('color: #ef4444')
        ->and($active)->not->toContain('#fff')
        ->and($active)->not->toContain('255,255,255')
        ->and($activeHover)->toContain('background-color: rgba(239,68,68,0.10)')
        ->and($activeHover)->not->toContain('#fff')
        ->and($hover)->toContain('background-color: rgba(255,255,255,0.10)');
});
