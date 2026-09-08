<?php

use App\Models\Booking;
use App\Models\Car;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

function homepageReview(array $attributes = []): Review
{
    $user = $attributes['user'] ?? User::factory()->create([
        'name' => 'Yassir Bakkas',
    ]);

    $car = $attributes['car'] ?? Car::factory()->create([
        'brand' => 'Peugeot',
        'model' => '208',
        'year' => 2022,
    ]);

    $booking = $attributes['booking'] ?? Booking::factory()->create([
        'user_id' => $user->id,
        'car_id' => $car->id,
        'status' => Booking::STATUS_COMPLETED,
    ]);

    unset($attributes['user'], $attributes['car'], $attributes['booking']);

    return Review::create(array_merge([
        'user_id' => $user->id,
        'car_id' => $car->id,
        'booking_id' => $booking->id,
        'rating' => 5,
        'comment' => 'Excellent rental experience from start to finish.',
        'is_approved' => true,
        'approved_at' => now(),
    ], $attributes));
}

test('approved review appears on homepage', function () {
    homepageReview([
        'comment' => 'The approved homepage review is visible.',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('The approved homepage review is visible.');
});

test('pending unapproved review does not appear on homepage', function () {
    homepageReview([
        'comment' => 'This pending review must stay hidden.',
        'is_approved' => false,
        'approved_at' => null,
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('This pending review must stay hidden.');
});

test('rejected hidden review does not appear on homepage', function () {
    $review = homepageReview([
        'comment' => 'This hidden review must disappear.',
    ]);

    $review->update([
        'is_approved' => false,
        'approved_at' => null,
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('This hidden review must disappear.');
});

test('real customer name and review comment appear on homepage', function () {
    homepageReview([
        'user' => User::factory()->create(['name' => 'Amina Idrissi']),
        'comment' => 'A real customer review comment appears here.',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Amina Idrissi')
        ->assertSee('A real customer review comment appears here.');
});

test('user profile photo renders without exposing private user fields', function () {
    Storage::fake('public');
    Storage::disk('public')->put('profile-photos/amina.jpg', 'avatar');

    $user = User::factory()->create([
        'name' => 'Amina Idrissi',
        'email' => 'amina.private@example.test',
        'phone' => '0612345678',
        'address' => 'Hidden Customer Address',
        'profile_photo_path' => 'profile-photos/amina.jpg',
    ]);

    homepageReview([
        'user' => $user,
        'comment' => 'Profile photo review renders from the public disk.',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('/storage/profile-photos/amina.jpg', false)
        ->assertSee('alt="Amina Idrissi"', false)
        ->assertDontSee('amina.private@example.test')
        ->assertDontSee('0612345678')
        ->assertDontSee('Hidden Customer Address');
});

test('user without profile photo renders initials fallback', function () {
    homepageReview([
        'user' => User::factory()->create([
            'name' => 'Yassir Bakkas',
            'profile_photo_path' => null,
        ]),
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<div class="ht-avatar-initials" aria-hidden="true">YB</div>', false);
});

test('actual review rating controls testimonial star output', function () {
    homepageReview([
        'rating' => 4,
        'comment' => 'The rating should render four filled stars.',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('aria-label="4 out of 5 stars"', false)
        ->assertSee('ht-star--off', false);
});

test('customer review html content is escaped on homepage', function () {
    $comment = '<script>alert("x")</script> Helpful rental review.';

    homepageReview([
        'comment' => $comment,
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee(e($comment), false)
        ->assertDontSee($comment, false);
});

test('associated car name appears safely on testimonial card', function () {
    homepageReview([
        'car' => Car::factory()->create([
            'brand' => 'Peugeot',
            'model' => '208',
            'year' => 2023,
        ]),
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('2023 Peugeot 208');
});

test('real review date and car context render on testimonial card', function () {
    $review = homepageReview([
        'car' => Car::factory()->create([
            'brand' => 'Renault',
            'model' => 'Clio',
            'year' => 2021,
        ]),
        'comment' => 'Date and car context should render.',
    ]);

    $review->forceFill([
        'created_at' => now()->subDays(2),
    ])->save();

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('2 days ago')
        ->assertSee('Reviewed')
        ->assertSee('2021 Renault Clio');
});

test('homepage works with zero approved reviews', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('class="home-testi"', false)
        ->assertDontSee('class="swiper ht-swiper"', false);
});

test('homepage works with one approved review', function () {
    homepageReview([
        'comment' => 'Only one approved review renders cleanly.',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Only one approved review renders cleanly.')
        ->assertSee('data-testimonial-count="1"', false)
        ->assertDontSee('<div class="swiper-pagination ht-pagination"></div>', false);
});

test('existing car details still only shows approved reviews', function () {
    $car = Car::factory()->create();

    homepageReview([
        'car' => $car,
        'comment' => 'Approved car details review is visible.',
        'is_approved' => true,
    ]);

    homepageReview([
        'car' => $car,
        'comment' => 'Pending car details review is hidden.',
        'is_approved' => false,
        'approved_at' => null,
    ]);

    $this->get(route('cars.show', $car))
        ->assertOk()
        ->assertSee('Approved car details review is visible.')
        ->assertDontSee('Pending car details review is hidden.');
});
