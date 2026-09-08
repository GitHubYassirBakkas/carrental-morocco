<?php

use App\Models\Setting;

if (! function_exists('setting')) {
    /**
     * Get setting value
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('ui_label')) {
    function ui_label(string $group, mixed $value, ?string $fallback = null): string
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            return $fallback ?? '';
        }

        $normalized = str($raw)
            ->lower()
            ->replace(['-', ' '], '_')
            ->replaceMatches('/[^a-z0-9_]+/', '')
            ->replaceMatches('/_+/', '_')
            ->trim('_')
            ->toString();

        $key = "messages.{$group}.{$normalized}";
        $translation = __($key);

        if ($translation !== $key) {
            return $translation;
        }

        return $fallback ?? str($raw)->replace(['_', '-'], ' ')->headline()->toString();
    }
}

if (! function_exists('ui_status')) {
    function ui_status(mixed $status): string
    {
        return ui_label('statuses', $status);
    }
}

if (! function_exists('ui_car_type')) {
    function ui_car_type(mixed $type): string
    {
        return ui_label('car_types', $type);
    }
}

if (! function_exists('ui_transmission')) {
    function ui_transmission(mixed $transmission): string
    {
        return ui_label('transmissions', $transmission);
    }
}

if (! function_exists('ui_fuel_type')) {
    function ui_fuel_type(mixed $fuelType): string
    {
        return ui_label('fuel_types', $fuelType);
    }
}

if (! function_exists('ui_payment_method')) {
    function ui_payment_method(mixed $method): string
    {
        return ui_label('payment_methods', $method);
    }
}
