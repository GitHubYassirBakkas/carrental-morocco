<?php

use App\Models\Car;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function low5Admin(): User
{
    return User::factory()->create([
        'role' => 'admin',
        'is_banned' => false,
    ]);
}

function low5Location(): Location
{
    return Location::factory()->create([
        'name' => 'Low 5 Upload Desk',
        'city' => 'Casablanca',
    ]);
}

function low5CarPayload(Location $location, array $overrides = []): array
{
    return array_merge([
        'brand' => 'Renault',
        'model' => 'Clio',
        'year' => 2024,
        'type' => 'Economy',
        'transmission' => 'Manual',
        'fuel_type' => 'Petrol',
        'seats' => 5,
        'doors' => 4,
        'luggage' => 2,
        'price_per_day' => 300,
        'image' => UploadedFile::fake()->image('car.jpg')->size(256),
        'location_id' => $location->id,
        'is_available' => true,
        'security_deposit_amount' => 0,
    ], $overrides);
}

function low5WebpUpload(string $name = 'car.webp'): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'low5-webp-');
    file_put_contents($path, base64_decode('UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEADsD+JaQAA3AA/vuUAAA='));

    return new UploadedFile($path, $name, 'image/webp', null, true);
}

function low5TextUpload(string $name, string $mime, string $contents): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'low5-upload-');
    file_put_contents($path, $contents);

    return new UploadedFile($path, $name, $mime, null, true);
}

test('admin car create accepts jpeg png and webp public marketing images', function () {
    Storage::fake('public');

    $admin = low5Admin();
    $location = low5Location();

    $uploads = [
        'jpeg' => UploadedFile::fake()->image('front.jpeg')->size(256),
        'jpg' => UploadedFile::fake()->image('front.jpg')->size(256),
        'png' => UploadedFile::fake()->image('front.png')->size(256),
        'webp' => low5WebpUpload('front.webp'),
    ];

    foreach ($uploads as $suffix => $upload) {
        $this->actingAs($admin)
            ->post(route('admin.cars.store'), low5CarPayload($location, [
                'brand' => 'Low5'.$suffix,
                'image' => $upload,
            ]))
            ->assertRedirect(route('admin.cars.index'));

        $car = Car::where('brand', 'Low5'.$suffix)->firstOrFail();

        expect($car->image)->not->toContain('/')
            ->and($car->image)->not->toContain('front');

        Storage::disk('public')->assertExists('cars/'.$car->image);
    }
});

test('admin car create rejects svg executable and text files masquerading as images', function () {
    Storage::fake('public');

    $admin = low5Admin();
    $location = low5Location();

    $badUploads = [
        'svg' => UploadedFile::fake()->createWithContent('car.svg', '<svg><script>alert(1)</script></svg>'),
        'php renamed jpg' => low5TextUpload('shell.jpg', 'application/x-php', '<?php echo "owned";'),
        'html renamed jpg' => low5TextUpload('page.jpg', 'text/html', '<!doctype html><script>alert(1)</script>'),
        'xml renamed png' => low5TextUpload('feed.png', 'application/xml', '<?xml version="1.0"?><root />'),
        'webp content unsupported extension' => low5WebpUpload('car.avif'),
    ];

    foreach ($badUploads as $label => $upload) {
        $this->actingAs($admin)
            ->post(route('admin.cars.store'), low5CarPayload($location, [
                'brand' => 'Rejected '.$label,
                'image' => $upload,
            ]))
            ->assertSessionHasErrors('image');

        expect(Car::where('brand', 'Rejected '.$label)->exists())->toBeFalse();
    }
});

test('admin car create rejects oversized public car images', function () {
    Storage::fake('public');

    $this->actingAs(low5Admin())
        ->post(route('admin.cars.store'), low5CarPayload(low5Location(), [
            'image' => UploadedFile::fake()->image('huge.jpg')->size(5121),
        ]))
        ->assertSessionHasErrors('image');
});

test('admin car gallery accepts valid public images and stores them on public disk', function () {
    Storage::fake('public');

    $location = low5Location();

    $this->actingAs(low5Admin())
        ->post(route('admin.cars.store'), low5CarPayload($location, [
            'brand' => 'GalleryValid',
            'gallery' => [
                UploadedFile::fake()->image('gallery-one.jpg')->size(100),
                UploadedFile::fake()->image('gallery-two.png')->size(100),
                low5WebpUpload('gallery-three.webp'),
            ],
        ]))
        ->assertRedirect(route('admin.cars.index'));

    $car = Car::where('brand', 'GalleryValid')->firstOrFail();

    expect($car->gallery)->toHaveCount(3);

    foreach ($car->gallery as $image) {
        expect($image)->not->toContain('/');
        Storage::disk('public')->assertExists('cars/'.$image);
    }
});

test('admin car gallery rejects any invalid image item', function () {
    Storage::fake('public');

    $location = low5Location();

    $this->actingAs(low5Admin())
        ->post(route('admin.cars.store'), low5CarPayload($location, [
            'brand' => 'GalleryInvalid',
            'gallery' => [
                UploadedFile::fake()->image('valid.jpg')->size(100),
                low5TextUpload('invalid.jpg', 'text/html', '<html></html>'),
            ],
        ]))
        ->assertSessionHasErrors('gallery.1');

    expect(Car::where('brand', 'GalleryInvalid')->exists())->toBeFalse();
});

test('admin car update uses the same image allow list and preserves public disk behavior', function () {
    Storage::fake('public');

    $admin = low5Admin();
    $location = low5Location();
    $car = Car::factory()->create([
        'location_id' => $location->id,
        'image' => 'old.jpg',
        'gallery' => ['old-gallery.jpg'],
    ]);

    Storage::disk('public')->put('cars/old.jpg', 'old image');
    Storage::disk('public')->put('cars/old-gallery.jpg', 'old gallery');

    $payload = low5CarPayload($location, [
        'brand' => $car->brand,
        'model' => $car->model,
        'image' => low5WebpUpload('replacement.webp'),
        'gallery' => [UploadedFile::fake()->image('new-gallery.png')->size(100)],
    ]);

    $this->actingAs($admin)
        ->put(route('admin.cars.update', $car), $payload)
        ->assertRedirect(route('admin.cars.index'));

    $car->refresh();

    expect($car->image)->not->toBe('old.jpg')
        ->and($car->gallery)->toHaveCount(2);

    Storage::disk('public')->assertMissing('cars/old.jpg');
    Storage::disk('public')->assertExists('cars/'.$car->image);
    Storage::disk('public')->assertExists('cars/'.$car->gallery[1]);

    $rejectedPayload = low5CarPayload($location, [
        'brand' => $car->brand,
        'model' => $car->model,
        'image' => low5TextUpload('replacement.jpg', 'text/html', '<html></html>'),
    ]);

    $this->actingAs($admin)
        ->put(route('admin.cars.update', $car), $rejectedPayload)
        ->assertSessionHasErrors('image');
});

test('admin car gallery limit remains bounded at ten images', function () {
    Storage::fake('public');

    $location = low5Location();
    $car = Car::factory()->create([
        'location_id' => $location->id,
        'gallery' => array_fill(0, 9, 'existing.jpg'),
    ]);

    $payload = low5CarPayload($location, [
        'brand' => $car->brand,
        'model' => $car->model,
        'image' => null,
        'gallery' => [
            UploadedFile::fake()->image('one.jpg')->size(100),
            UploadedFile::fake()->image('two.jpg')->size(100),
        ],
    ]);
    unset($payload['image']);

    $this->actingAs(low5Admin())
        ->put(route('admin.cars.update', $car), $payload)
        ->assertSessionHasErrors('gallery');
});
