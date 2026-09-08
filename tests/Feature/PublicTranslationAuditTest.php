<?php

use App\Models\Booking;
use App\Models\Car;
use App\Models\CustomerProfile;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;

function translationAuditLocation(array $attributes = []): Location
{
    return Location::factory()->create(array_merge([
        'name' => 'Meknes Branch',
        'address' => 'Avenue Mohammed V, Meknes',
        'city' => 'Meknes',
        'country' => 'Morocco',
        'phone' => '+212 5 35 52 00 00',
        'email' => 'meknes@example.test',
        'is_active' => true,
    ], $attributes));
}

function translationAuditCar(array $attributes = []): Car
{
    $location = $attributes['location'] ?? translationAuditLocation();
    unset($attributes['location']);

    return Car::factory()->create(array_merge([
        'brand' => 'BMW',
        'model' => 'X5',
        'type' => 'Luxury',
        'transmission' => 'Manual',
        'fuel_type' => 'Petrol',
        'is_available' => true,
        'location_id' => $location->id,
        'security_deposit_amount' => 2500,
    ], $attributes));
}

function translationAuditSetting(string $key, string $value): void
{
    Setting::query()->updateOrCreate(
        ['key' => $key],
        [
            'value' => $value,
            'type' => 'text',
            'group' => 'general',
            'label' => str($key)->replace('_', ' ')->title()->toString(),
            'autoload' => true,
            'is_public' => true,
        ],
    );

    Setting::clearCache();
}

function translationAuditBootstrap(): array
{
    $location = translationAuditLocation();
    $car = translationAuditCar(['location' => $location]);

    translationAuditSetting('site_phone', '+212 5 35 52 00 00');
    translationAuditSetting('site_email', 'meknes@example.test');
    translationAuditSetting('site_address', 'Avenue Mohammed V, Meknes');

    return [$location, $car];
}

function translationAuditBooking(User $user, Car $car, Location $location, array $attributes = []): Booking
{
    return Booking::factory()->create(array_merge([
        'user_id' => $user->id,
        'car_id' => $car->id,
        'pickup_location_id' => $location->id,
        'dropoff_location_id' => $location->id,
        'start_date' => now()->addDays(3),
        'end_date' => now()->addDays(5),
        'rental_price_per_day' => 900,
        'total_amount' => 1800,
        'advance_payment_amount' => 1800,
        'advance_payment_status' => 'paid',
        'security_deposit_amount' => 0,
        'status' => Booking::STATUS_PENDING,
    ], $attributes));
}

test('homepage navbar and footer translate in english french and arabic without Meknes corruption', function (string $locale, string $expectedHome, string $expectedCars, string $expectedFooter, string $expectedBrands) {
    translationAuditBootstrap();

    $response = $this->withSession(['locale' => $locale])
        ->get(route('home'))
        ->assertOk()
        ->assertSee($expectedHome)
        ->assertSee($expectedCars)
        ->assertSee($expectedFooter)
        ->assertSee($expectedBrands)
        ->assertSee('Avenue Mohammed V, Meknes')
        ->assertDontSee('Mekn?s')
        ->assertDontSee('MESSAGES')
        ->assertDontSee('home_brands_label');

    $response->assertSee('lang="'.$locale.'"', false);

    if ($locale === 'ar') {
        $response->assertSee('dir="rtl"', false);
    } else {
        $response->assertSee('dir="ltr"', false);
    }
})->with([
    ['en', 'Home', 'Cars Available', 'Write to us', 'Our Brands'],
    ['fr', 'Accueil', 'Voitures disponibles', 'Écrivez-nous', 'Nos marques'],
    ['ar', 'الرئيسية', 'السيارات المتاحة', 'راسلنا', 'علاماتنا التجارية'],
]);

test('car listing and details translate presentation labels without changing brand names', function () {
    [, $car] = translationAuditBootstrap();

    $this->withSession(['locale' => 'fr'])
        ->get(route('cars.index'))
        ->assertOk()
        ->assertSee('BMW X5')
        ->assertSee('Luxe')
        ->assertSee('Manuelle');

    $this->withSession(['locale' => 'ar'])
        ->get(route('cars.show', $car))
        ->assertOk()
        ->assertSee('BMW X5')
        ->assertSee('فاخرة')
        ->assertSee('يدوي')
        ->assertSee('بنزين')
        ->assertDontSee('Mekn?s');
});

test('booking preview payment profile driver notifications and support pages use translated customer labels', function () {
    [$location, $car] = translationAuditBootstrap();
    $user = User::factory()->create();
    $booking = translationAuditBooking($user, $car, $location);
    $invoice = Invoice::factory()->create([
        'booking_id' => $booking->id,
        'user_id' => $user->id,
        'subtotal' => 1800,
        'total_amount' => 1800,
        'status' => Invoice::STATUS_PAID,
    ]);
    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'user_id' => $user->id,
        'amount' => 1800,
        'method' => 'card',
        'status' => Payment::STATUS_COMPLETED,
        'type' => Payment::TYPE_PAYMENT,
    ]);
    $profile = CustomerProfile::query()->create([
        'user_id' => $user->id,
        'date_of_birth' => now()->subYears(30)->toDateString(),
        'driving_license_number' => 'DRV-12345',
        'driving_license_front_path' => 'customer-documents/test/license-front.jpg',
        'driving_license_back_path' => 'customer-documents/test/license-back.jpg',
        'identity_front_path' => 'customer-documents/test/cnie-front.jpg',
        'identity_back_path' => 'customer-documents/test/cnie-back.jpg',
    ]);
    $profile->forceFill([
        'driver_verification_status' => CustomerProfile::STATUS_VERIFIED,
        'driver_verified_at' => now(),
    ])->save();

    $preview = [
        'car_id' => $car->id,
        'pickup_location_id' => $location->id,
        'dropoff_location_id' => $location->id,
        'pickup_date' => now()->addDays(3)->toDateString(),
        'return_date' => now()->addDays(5)->toDateString(),
        'pickup_time' => '10:00',
        'return_time' => '10:00',
        'start_date' => now()->addDays(3)->setTime(10, 0)->toDateTimeString(),
        'end_date' => now()->addDays(5)->setTime(10, 0)->toDateTimeString(),
        'days' => 2,
        'car_price' => 900,
        'insurance_id' => null,
        'insurance_price' => 0,
        'car_total' => 1800,
        'insurance_total' => 0,
        'dropoff_fee' => 0,
        'rental_amount' => 1800,
        'insurance_amount' => 0,
        'protection_plan_amount' => 0,
        'extras_amount' => 0,
        'pricing_breakdown' => ['total_amount' => 1800],
        'grand_total' => 1800,
    ];

    $this->actingAs($user)->withSession(['locale' => 'fr', 'booking_preview' => $preview])
        ->get(route('bookings.preview.show'))
        ->assertOk()
        ->assertSee('Vérifiez votre réservation')
        ->assertSee('Manuelle')
        ->assertSee('Essence');

    $this->actingAs($user)->withSession(['locale' => 'ar'])
        ->get(route('payments.show', $booking))
        ->assertOk()
        ->assertSee('دفع آمن')
        ->assertSee('تم دفع مبلغ الإيجار مسبقاً')
        ->assertSee('ملخص الطلب');

    $this->actingAs($user)->withSession(['locale' => 'fr'])
        ->get(route('my_booking.index'))
        ->assertOk()
        ->assertSee('En attente')
        ->assertDontSee('>pending<', false);

    $this->actingAs($user)->withSession(['locale' => 'ar'])
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('التحقق من بيانات السائق')
        ->assertSee('تم التحقق');

    $this->actingAs($user)->withSession(['locale' => 'ar'])
        ->get(route('profile.driver.edit'))
        ->assertOk()
        ->assertSee('إكمال ملف السائق')
        ->assertSee('رخصة السياقة');

    $this->actingAs($user)->withSession(['locale' => 'fr'])
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('Mes Notifications');

    $this->actingAs($user)->withSession(['locale' => 'fr'])
        ->get(route('tickets.index'))
        ->assertOk()
        ->assertSee('Centre de Support');
});

test('legal pages have localized body copy in all public locales', function (string $locale, string $privacy, string $terms, string $notice) {
    translationAuditBootstrap();

    $this->withSession(['locale' => $locale])
        ->get(route('legal.privacy'))
        ->assertOk()
        ->assertSee($privacy)
        ->assertDontSee('messages.legal_content');

    $this->withSession(['locale' => $locale])
        ->get(route('legal.terms'))
        ->assertOk()
        ->assertSee($terms)
        ->assertDontSee('messages.legal_content');

    $this->withSession(['locale' => $locale])
        ->get(route('legal.notice'))
        ->assertOk()
        ->assertSee($notice)
        ->assertSee('Avenue Mohammed V, Meknes')
        ->assertDontSee('Mekn?s')
        ->assertDontSee('messages.legal_content');
})->with([
    ['en', 'Information we collect', 'Driver responsibilities', 'Unavailable legal identifiers'],
    ['fr', 'Informations collectées', 'Responsabilités du conducteur', 'Identifiants légaux non disponibles'],
    ['ar', 'المعلومات التي نجمعها', 'مسؤوليات السائق', 'المعرفات القانونية غير المتاحة'],
]);

test('presentation helpers translate statuses car attributes and payment methods only for display', function () {
    app()->setLocale('fr');

    expect(ui_status('pending'))->toBe('En attente')
        ->and(ui_status('refunded'))->toBe('Remboursé')
        ->and(ui_car_type('Luxury'))->toBe('Luxe')
        ->and(ui_transmission('Manual'))->toBe('Manuelle')
        ->and(ui_fuel_type('Petrol'))->toBe('Essence')
        ->and(ui_payment_method('bank_transfer'))->toBe('Virement bancaire');

    app()->setLocale('ar');

    expect(ui_status('verified'))->toBe('تم التحقق')
        ->and(ui_car_type('Family / Van'))->toBe('عائلية / فان')
        ->and(ui_transmission('Automatic'))->toBe('أوتوماتيك')
        ->and(ui_fuel_type('Electric'))->toBe('كهربائية');
});

test('language switcher stores safe locales and returns to the previous page', function () {
    translationAuditBootstrap();

    $this->from(route('cars.index'))
        ->get(route('language.switch', 'ar'))
        ->assertRedirect(route('cars.index'))
        ->assertSessionHas('locale', 'ar');

    $this->from(route('cars.index'))
        ->get(route('language.switch', 'xx'))
        ->assertRedirect(route('cars.index'))
        ->assertSessionMissing('locale', 'xx');
});
