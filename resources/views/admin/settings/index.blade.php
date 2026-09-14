@extends('admin.layouts.app')

@section('content')
<div class="min-h-screen bg-[#0a0e1a] text-gray-100 pb-32">
    <div class="max-w-6xl mx-auto p-8">

        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-white mb-2">Settings</h1>
            <p class="text-gray-400">Manage your application settings and configuration</p>
        </div>

        <!-- Success Alert -->
        @if(session('success'))
            <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-lg p-4 mb-6">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-emerald-300">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        <!-- Settings Form -->
        <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-6" id="settings-form">
            @csrf
            @method('PUT')

            @foreach($settings as $group => $groupSettings)
                
                <!-- Group Card -->
                <div class="bg-[#1a2332] border border-gray-800 rounded-xl overflow-hidden">
                    
                    <!-- Group Header -->
                    <div class="bg-[#0f1520] border-b border-gray-800 px-8 py-4">
                        <h2 class="text-xl font-bold text-white flex items-center gap-3">
                            @if($group === 'general')
                                <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                </svg>
                                General Settings
                            @elseif($group === 'payment')
                                <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                Payment Settings
                            @elseif($group === 'booking')
                                <svg class="w-6 h-6 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                Booking Settings
                            @elseif($group === 'refund')
                                <svg class="w-6 h-6 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                                Refund Policy
                            @elseif($group === 'social')
                                <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h6m5 8l-4-4H7a4 4 0 01-4-4V7a4 4 0 014-4h10a4 4 0 014 4v5a4 4 0 01-4 4h-1"/>
                                </svg>
                                Social Media
                            @else
                                <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                                </svg>
                                {{ ucfirst($group) }} Settings
                            @endif
                        </h2>
                    </div>

                    <!-- Group Settings -->
                    <div class="p-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            @foreach($groupSettings as $setting)
                                {{-- Legacy refund toggles are kept in storage for backward compatibility; the active policy is the pickup-based three-band rule. --}}
                                @continue(in_array($setting->key, ['refund_free_cancellation_enabled', 'refund_no_refund_enabled'], true))
                                @php
                                    $isReadonlyCurrency = $setting->key === 'currency';
                                    $isInactiveFuelLiterRule = $setting->key === 'fuel_price_per_liter';
                                    $displayLabel = match ($setting->key) {
                                        'refund_cancellation_window_hours' => 'Full Refund Before Pickup (Hours)',
                                        'refund_partial_refund_cutoff_hours' => 'Partial Refund Until Pickup (Hours)',
                                        'refund_partial_percentage' => 'Partial Refund Percentage',
                                        'refund_default_method' => 'Fallback Refund Method',
                                        'fuel_price_per_percent' => 'Fuel Fee Per Missing Tank % (MAD)',
                                        'currency' => 'Currency',
                                        default => $setting->label,
                                    };
                                    $displayDescription = match ($setting->key) {
                                        'currency' => 'MAD - application currency',
                                        'fuel_price_per_liter' => 'Inactive: the production fuel rule charges per missing tank percent.',
                                        'refund_cancellation_window_hours' => 'Scheduled pickup must be at least this many hours away for a full rental payment refund.',
                                        'refund_partial_refund_cutoff_hours' => 'Scheduled pickup must be at least this many hours away for the configured partial refund.',
                                        default => $setting->description,
                                    };
                                @endphp
                                {{-- fuel_price_per_liter is intentionally not editable until a real liter-based calculation path exists. --}}
                                <div class="@if($setting->type === 'textarea') md:col-span-2 @endif">
                                    
                                    <!-- Setting Label -->
                                    <label class="block text-sm font-semibold text-gray-300 mb-2">
                                        {{ $displayLabel }}
                                        @if($displayDescription)
                                            <span class="block text-xs font-normal text-gray-500 mt-1">
                                                {{ $displayDescription }}
                                            </span>
                                        @endif
                                    </label>

                                    <!-- Setting Input -->
                                    @if($isReadonlyCurrency)
                                        <input type="text"
                                               value="MAD - application currency"
                                               readonly
                                               class="w-full px-4 py-3 bg-black/30 border border-gray-800 rounded-xl text-gray-400 cursor-not-allowed">

                                    @elseif($isInactiveFuelLiterRule)
                                        <input type="number"
                                               value="{{ old($setting->key, $setting->value) }}"
                                               step="0.01"
                                               disabled
                                               class="w-full px-4 py-3 bg-black/30 border border-gray-800 rounded-xl text-gray-500 cursor-not-allowed">

                                    @elseif($setting->key === 'refund_default_method')
                                        <select name="{{ $setting->key }}"
                                                class="w-full px-4 py-3 bg-black/40 border border-gray-700 rounded-xl text-white focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                            <option value="cash" @selected(old($setting->key, $setting->value) === 'cash')>Cash</option>
                                            <option value="bank_transfer" @selected(old($setting->key, $setting->value) === 'bank_transfer')>Bank Transfer</option>
                                        </select>

                                    @elseif($setting->type === 'text')
                                        <input type="text" 
                                               name="{{ $setting->key }}" 
                                               value="{{ old($setting->key, $setting->value) }}"
                                               class="w-full px-4 py-3 bg-black/40 border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                    
                                    @elseif($setting->type === 'textarea')
                                        <textarea name="{{ $setting->key }}" 
                                                  rows="3"
                                                  class="w-full px-4 py-3 bg-black/40 border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-orange-500 focus:border-transparent">{{ old($setting->key, $setting->value) }}</textarea>
                                    
                                    @elseif($setting->type === 'number' || $setting->type === 'integer')
                                        <input type="number" 
                                               name="{{ $setting->key }}" 
                                               value="{{ old($setting->key, $setting->value) }}"
                                               step="{{ $setting->type === 'number' ? '0.01' : '1' }}"
                                               class="w-full px-4 py-3 bg-black/40 border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                    
                                    @elseif($setting->type === 'boolean')
                                        <div class="flex items-center gap-3 p-4 bg-black/30 rounded-xl">
                                            <input type="hidden" name="{{ $setting->key }}" value="0">
                                            <input type="checkbox" 
                                                   name="{{ $setting->key }}" 
                                                   value="1"
                                                   {{ old($setting->key, $setting->value) ? 'checked' : '' }}
                                                   class="w-5 h-5 text-orange-600 bg-gray-700 border-gray-600 rounded focus:ring-orange-500 focus:ring-2">
                                            <span class="text-gray-300 text-sm">
                                                Enable {{ strtolower($setting->label) }}
                                            </span>
                                        </div>
                                    
                                    @elseif($setting->type === 'json' || $setting->type === 'array')
                                        <textarea name="{{ $setting->key }}" 
                                                  rows="4"
                                                  placeholder='{"key": "value"}'
                                                  class="w-full px-4 py-3 bg-black/40 border border-gray-700 rounded-xl text-white placeholder-gray-500 font-mono text-sm focus:ring-2 focus:ring-orange-500 focus:border-transparent">{{ old($setting->key, is_array($setting->value) ? json_encode($setting->value, JSON_PRETTY_PRINT) : $setting->value) }}</textarea>
                                    
                                    @else
                                        <input type="text" 
                                               name="{{ $setting->key }}" 
                                               value="{{ old($setting->key, $setting->value) }}"
                                               class="w-full px-4 py-3 bg-black/40 border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                    @endif

                                    <!-- Current Value Display -->
                                    <div class="mt-2 text-xs text-gray-500">
                                        Current: 
                                        @if($setting->type === 'boolean')
                                            <span class="px-2 py-1 rounded {{ $setting->value ? 'bg-emerald-900/30 text-emerald-400' : 'bg-gray-700 text-gray-400' }}">
                                                {{ $setting->value ? 'Enabled' : 'Disabled' }}
                                            </span>
                                        @else
                                            <span class="font-mono">{{ is_array($setting->value) ? json_encode($setting->value) : $setting->value }}</span>
                                        @endif
                                    </div>

                                </div>
                            @endforeach

                        </div>
                    </div>

                </div>

            @endforeach

        </form>

        <!-- Info Panel -->
        <div class="mt-8 bg-blue-500/10 border border-blue-500/30 rounded-xl p-6">
            <div class="flex items-start gap-4">
                <svg class="w-6 h-6 text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <h3 class="text-blue-300 font-semibold mb-2">Important Notes</h3>
                    <ul class="space-y-1 text-blue-200 text-sm">
                        <li>Changes apply on the next request.</li>
                        <li>Tax and advance payment percentages are used in booking calculations.</li>
                        <li>Minimum driver age affects booking eligibility.</li>
                        <li>Late fees are calculated automatically based on return time.</li>
                    </ul>
                    <ul class="hidden">
                        <li>• Settings are cached for performance - changes may take a few seconds to apply</li>
                        <li>• Tax and advance payment percentages are used in booking calculations</li>
                        <li>• Minimum driver age affects booking eligibility</li>
                        <li>• Late fees are calculated automatically based on return time</li>
                        <li>• Changes to payment settings affect new bookings only</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Fixed Bottom Action Buttons -->
<div class="fixed bottom-0 left-64 right-0 bg-[#0a0e1a]/95 backdrop-blur-sm border-t border-gray-800 p-4 z-50">
    <div class="max-w-6xl mx-auto px-8">
        <div class="flex gap-4">
            <button type="submit"
                    form="settings-form"
                    class="px-8 py-3 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-lg transition-all shadow-lg">
                💾 Save All Settings
            </button>

            <a href="{{ route('admin.dashboard') }}"
               class="px-8 py-3 bg-gray-600 hover:bg-gray-600 text-white font-semibold rounded-lg transition-all text-center whitespace-nowrap">
                Cancel
            </a>
        </div>
    </div>
</div>
@endsection
