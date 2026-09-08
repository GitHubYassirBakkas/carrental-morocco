<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Car;
use App\Models\Location;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PublicSiteDataService
{
    private ?EloquentCollection $activeLocations = null;

    private ?Collection $availableBrands = null;

    private ?array $contactInfo = null;

    private ?array $socialLinks = null;

    public function availableCarsCount(): int
    {
        return Car::available()->count();
    }

    public function availableBrandsCount(): int
    {
        return $this->availableBrands()->count();
    }

    public function completedCustomersCount(): int
    {
        return Booking::query()
            ->where('status', Booking::STATUS_COMPLETED)
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');
    }

    public function activeLocations(): EloquentCollection
    {
        return $this->activeLocations ??= Location::active()
            ->select([
                'id',
                'name',
                'address',
                'city',
                'country',
                'postal_code',
                'phone',
                'email',
                'opening_time',
                'closing_time',
            ])
            ->orderBy('id')
            ->get();
    }

    public function activeLocationsCount(): int
    {
        return Location::active()->count();
    }

    public function primaryLocation(): ?Location
    {
        return $this->activeLocations()->first();
    }

    public function availableBrands(): Collection
    {
        return $this->availableBrands ??= Car::available()
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->distinct()
            ->orderBy('brand')
            ->pluck('brand')
            ->map(fn (?string $brand): string => Str::squish((string) $brand))
            ->filter()
            ->unique(fn (string $brand): string => Str::lower($brand))
            ->values();
    }

    public function brands(): Collection
    {
        return $this->availableBrands();
    }

    public function locationOptions(): Collection
    {
        return $this->activeLocations()
            ->map(fn (Location $location): array => [
                'val' => (string) $location->id,
                'label' => $location->city ?: $location->name,
                'active' => true,
            ])
            ->values();
    }

    public function contactData(): array
    {
        if ($this->contactInfo !== null) {
            return $this->contactInfo;
        }

        $settings = Setting::getMany([
            'site_phone',
            'site_email',
            'site_address',
        ]);

        $primaryLocation = $this->primaryLocation();

        return $this->contactInfo = [
            'phone' => $this->firstNonBlank($settings['site_phone'] ?? null, $primaryLocation?->phone),
            'email' => $this->firstNonBlank($settings['site_email'] ?? null, $primaryLocation?->email),
            'address' => $this->firstNonBlank($settings['site_address'] ?? null, $primaryLocation?->full_address),
        ];
    }

    public function contactInfo(): array
    {
        return $this->contactData();
    }

    public function socialLinks(): array
    {
        if ($this->socialLinks !== null) {
            return $this->socialLinks;
        }

        $settings = Setting::getMany([
            'social_instagram_url',
            'social_whatsapp_url',
            'social_facebook_url',
        ]);

        return $this->socialLinks = array_filter([
            'instagram' => $this->socialLink('instagram', __('messages.instagram'), 'fa-brands fa-instagram', $settings['social_instagram_url'] ?? null),
            'whatsapp' => $this->socialLink('whatsapp', __('messages.whatsapp'), 'fa-brands fa-whatsapp', $settings['social_whatsapp_url'] ?? null),
            'facebook' => $this->socialLink('facebook', __('messages.facebook'), 'fa-brands fa-facebook-f', $settings['social_facebook_url'] ?? null),
        ]);
    }

    public function publicStats(): array
    {
        return [
            'available_cars' => $this->availableCarsCount(),
            'completed_customers' => $this->completedCustomersCount(),
            'active_locations' => $this->activeLocationsCount(),
            'active_brands' => $this->availableBrandsCount(),
        ];
    }

    public function stats(): array
    {
        return $this->publicStats();
    }

    public function summary(): array
    {
        return [
            'stats' => $this->publicStats(),
            'brands' => $this->availableBrands(),
            'activeLocations' => $this->activeLocations(),
            'locationOptions' => $this->locationOptions(),
            'primaryLocation' => $this->primaryLocation(),
            'contact' => $this->contactData(),
            'socialLinks' => $this->socialLinks(),
        ];
    }

    private function socialLink(string $key, string $label, string $icon, ?string $url): ?array
    {
        $url = $this->firstNonBlank($url);

        if (! $this->isPublicHttpUrl($url)) {
            return null;
        }

        return [
            'key' => $key,
            'label' => $label,
            'icon' => $icon,
            'url' => $url,
        ];
    }

    private function isPublicHttpUrl(?string $url): bool
    {
        if ($url === null || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        return in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true);
    }

    private function firstNonBlank(?string ...$values): ?string
    {
        foreach ($values as $value) {
            $value = $value === null ? null : Str::squish($value);

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}
