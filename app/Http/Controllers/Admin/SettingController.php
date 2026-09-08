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
        // Get all settings from request
        $settingsData = $request->except('_token', '_method');
        
        foreach ($settingsData as $key => $value) {
            $setting = Setting::where('key', $key)->first();
            
            if ($setting) {
                // Handle checkboxes (boolean)
                if ($setting->type === 'boolean') {
                    $value = $request->has($key) ? 1 : 0;
                }
                
                // Update setting
                Setting::set($key, $value);
            }
        }
        
        return back()->with('success', 'Settings updated successfully!');
    }
}