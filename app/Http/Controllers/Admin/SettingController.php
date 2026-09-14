<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\RefundPolicyService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SettingController extends Controller
{
    /**
     * Display settings (grouped)
     */
    public function index()
    {
        // Get all settings grouped
        $settings = Setting::orderBy('group')->orderBy('id')->get()->groupBy('group');

        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Update settings
     */
    public function update(Request $request)
    {
        $settings = Setting::all();
        $request->validate($this->validationRules($settings));
        $this->validateRefundWindows($request, $settings);

        foreach ($settings as $setting) {
            $key = $setting->key;
            $type = $setting->getRawOriginal('type') ?: 'text';

            if (! $request->has($key)) {
                continue;
            }

            if ($type === 'boolean') {
                Setting::set($key, $request->boolean($key), $type);

                continue;
            }

            Setting::set($key, $request->input($key), $type);
        }

        return back()->with('success', 'Settings updated successfully!');
    }

    private function validationRules($settings): array
    {
        return $settings->mapWithKeys(function (Setting $setting) {
            $key = str_replace('.', '\\.', $setting->key);
            $type = $setting->getRawOriginal('type') ?: 'text';

            $rules = match ($setting->key) {
                'booking_min_days' => ['sometimes', 'integer', 'min:1', 'max:365'],
                'booking_max_days' => ['sometimes', 'integer', 'gte:booking_min_days', 'max:365'],
                'max_advance_booking_days' => ['sometimes', 'integer', 'min:0', 'max:730'],
                'advance_payment_deadline_hours' => ['sometimes', 'integer', 'min:1', 'max:720'],
                'min_driver_age' => ['sometimes', 'integer', 'min:18', 'max:100'],
                'advance_payment_percentage' => ['sometimes', 'numeric', 'min:0', 'max:100'],
                'tax_percentage' => ['sometimes', 'numeric', 'min:0', 'max:100'],
                'late_fee_per_hour' => ['sometimes', 'numeric', 'min:0', 'max:100000'],
                'late_grace_minutes' => ['sometimes', 'integer', 'min:0', 'max:10080'],
                'dropoff_fee' => ['sometimes', 'numeric', 'min:0', 'max:100000'],
                'fuel_price_per_percent' => ['sometimes', 'numeric', 'min:0', 'max:100000'],
                'fuel_price_per_liter' => ['nullable', 'numeric', 'min:0', 'max:100000'],
                'refund_cancellation_window_hours' => ['sometimes', 'integer', 'min:1', 'max:8760'],
                'refund_partial_refund_cutoff_hours' => ['sometimes', 'integer', 'min:0', 'max:8760'],
                'refund_partial_percentage' => ['sometimes', 'numeric', 'min:0', 'max:100'],
                'refund_default_method' => ['sometimes', Rule::in(RefundPolicyService::FALLBACK_REFUND_METHODS)],
                'site_email' => ['sometimes', 'email', 'max:255'],
                'site_name' => ['sometimes', 'string', 'max:120'],
                'site_phone' => ['sometimes', 'string', 'max:50'],
                'site_address' => ['sometimes', 'string', 'max:500'],
                'currency' => ['nullable', Rule::in(['MAD'])],
                default => match ($type) {
                    'boolean' => ['nullable', 'boolean'],
                    'number', 'integer' => ['nullable', 'integer'],
                    'float' => ['nullable', 'numeric'],
                    'array', 'json' => ['nullable'],
                    default => ['nullable', 'string', 'max:5000'],
                },
            };

            if (in_array($setting->key, [
                'social_instagram_url',
                'social_whatsapp_url',
                'social_facebook_url',
            ], true)) {
                $rules = ['nullable', 'url:http,https', 'max:2048'];
            }

            return [$key => $rules];
        })->toArray();
    }

    private function validateRefundWindows(Request $request, $settings): void
    {
        if (! $settings->contains('key', 'refund_cancellation_window_hours')
            || ! $settings->contains('key', 'refund_partial_refund_cutoff_hours')) {
            return;
        }

        $fullRefundHours = (int) $request->input(
            'refund_cancellation_window_hours',
            Setting::get('refund_cancellation_window_hours', 48)
        );
        $partialRefundHours = (int) $request->input(
            'refund_partial_refund_cutoff_hours',
            Setting::get('refund_partial_refund_cutoff_hours', 24)
        );

        if ($fullRefundHours <= $partialRefundHours) {
            throw ValidationException::withMessages([
                'refund_cancellation_window_hours' => 'Full refund hours must be greater than the partial refund cutoff hours.',
            ]);
        }
    }
}
