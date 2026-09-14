<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'autoload',      // ← NEW
        'is_public',      // ← NEW
    ];

    protected $casts = [
        'autoload' => 'boolean',
        'is_public' => 'boolean',
    ];

    public $timestamps = false;

    /**
     * Accessor: Auto-cast value based on type
     */
    public function getValueAttribute($value)
    {
        return match ($this->type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'number', 'float' => static::castNumericValue($value),
            'array', 'json' => json_decode($value, true) ?: [],
            default => $value,
        };
    }

    private static function castNumericValue(mixed $value): int|float
    {
        $numeric = (float) $value;

        return floor($numeric) === $numeric ? (int) $numeric : $numeric;
    }

    /**
     * Get single setting value with caching
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        return Cache::rememberForever("setting_{$key}", function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set/Update setting value
     */
    public static function set(string $key, mixed $value, ?string $type = null): void
    {
        $setting = static::where('key', $key)->first();
        $type = $type ?? $setting?->getRawOriginal('type') ?? 'text';
        $group = $setting?->group;

        if (is_array($value) || is_object($value)) {
            $type = $type === 'array' ? 'array' : 'json';
            $value = json_encode($value);
        } elseif (in_array($type, ['array', 'json'], true) && is_string($value)) {
            json_decode($value);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $value = json_encode([$value]);
            }
        } elseif ($type === 'boolean') {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
        } elseif ($type === 'integer') {
            $value = (string) (int) $value;
        } elseif (in_array($type, ['number', 'float'], true)) {
            $value = (string) (float) $value;
        }

        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
            ]
        );

        // Clear cache
        Cache::forget("setting_{$key}");
        Cache::forget('autoload_settings');
        Cache::forget('public_settings');
        if ($group) {
            Cache::forget("settings_group_{$group}");
        }
    }

    /**
     * Get all autoload settings (for performance)
     */
    public static function getAutoloadSettings(): array
    {
        return Cache::rememberForever('autoload_settings', function () {
            return static::where('autoload', true)
                ->get()
                ->mapWithKeys(function ($setting) {
                    return [$setting->key => $setting->value];
                })
                ->toArray();
        });
    }

    /**
     * Get all public settings (safe for frontend)
     */
    public static function getPublicSettings(): array
    {
        return Cache::rememberForever('public_settings', function () {
            return static::where('is_public', true)
                ->get()
                ->mapWithKeys(function ($setting) {
                    return [$setting->key => $setting->value];
                })
                ->toArray();
        });
    }

    /**
     * Get all settings by group
     */
    public static function getByGroup(string $group): array
    {
        return Cache::rememberForever("settings_group_{$group}", function () use ($group) {
            return static::where('group', $group)
                ->get()
                ->mapWithKeys(function ($setting) {
                    return [$setting->key => $setting->value];
                })
                ->toArray();
        });
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache(): void
    {
        // Clear main caches
        Cache::forget('autoload_settings');
        Cache::forget('public_settings');

        // Clear individual setting caches
        static::all()->each(function ($setting) {
            Cache::forget("setting_{$setting->key}");
        });

        // Clear group caches
        static::select('group')->distinct()->pluck('group')->each(function ($group) {
            Cache::forget("settings_group_{$group}");
        });
    }

    /**
     * Refresh cache for a specific setting
     */
    public static function refreshCache(string $key): void
    {
        Cache::forget("setting_{$key}");
        static::get($key); // Re-cache it
    }

    /**
     * Check if setting exists
     */
    public static function has(string $key): bool
    {
        return static::where('key', $key)->exists();
    }

    /**
     * Delete a setting
     */
    public static function remove(string $key): bool
    {
        $deleted = static::where('key', $key)->delete();

        if ($deleted) {
            Cache::forget("setting_{$key}");
            static::clearCache();
        }

        return (bool) $deleted;
    }

    /**
     * Get multiple settings at once
     */
    public static function getMany(array $keys): array
    {
        return static::whereIn('key', $keys)
            ->get()
            ->mapWithKeys(function ($setting) {
                return [$setting->key => $setting->value];
            })
            ->toArray();
    }
}
