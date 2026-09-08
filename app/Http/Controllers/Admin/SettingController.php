<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

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

        foreach ($settings as $setting) {
            $key = $setting->key;
            $type = $setting->getRawOriginal('type') ?: 'text';

            if ($type === 'boolean') {
                Setting::set($key, $request->boolean($key), $type);
                continue;
            }

            if ($request->has($key)) {
                Setting::set($key, $request->input($key), $type);
            }
        }
        
        return back()->with('success', 'Settings updated successfully!');
    }

    private function validationRules($settings): array
    {
        return $settings->mapWithKeys(function (Setting $setting) {
            $key = str_replace('.', '\\.', $setting->key);
            $type = $setting->getRawOriginal('type') ?: 'text';

            $rules = match ($type) {
                'boolean' => ['nullable', 'boolean'],
                'number', 'integer' => ['nullable', 'integer'],
                'float' => ['nullable', 'numeric'],
                'array', 'json' => ['nullable'],
                default => ['nullable', 'string', 'max:5000'],
            };

            return [$key => $rules];
        })->toArray();
    }
}
