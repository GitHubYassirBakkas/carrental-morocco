@extends('layouts.app')

@section('title', __('messages.account_title'))

@section('content')

@php
    $profile = $user->customerProfile;
    $driverStatus = $profile?->driver_verification_status ?? \App\Models\CustomerProfile::STATUS_INCOMPLETE;
    $driverStatusLabels = [
        \App\Models\CustomerProfile::STATUS_INCOMPLETE => ui_status('incomplete'),
        \App\Models\CustomerProfile::STATUS_PENDING => __('messages.status_pending_verification'),
        \App\Models\CustomerProfile::STATUS_VERIFIED => ui_status('verified'),
        \App\Models\CustomerProfile::STATUS_REJECTED => ui_status('rejected'),
    ];
    $driverStatusClasses = [
        \App\Models\CustomerProfile::STATUS_INCOMPLETE => 'text-amber-300 bg-amber-500/10 border-amber-500/30',
        \App\Models\CustomerProfile::STATUS_PENDING => 'text-blue-300 bg-blue-500/10 border-blue-500/30',
        \App\Models\CustomerProfile::STATUS_VERIFIED => 'text-emerald-300 bg-emerald-500/10 border-emerald-500/30',
        \App\Models\CustomerProfile::STATUS_REJECTED => 'text-red-300 bg-red-500/10 border-red-500/30',
    ];
@endphp

@push('styles')
<style>
.footer { background: #0b0d12; color: #ccc; padding-top: 60px; }
.footer-top { max-width: 1200px; margin: auto; background: #11131a; border-radius: 18px; padding: 35px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; }
.footer-box { display: flex; align-items: center; gap: 15px; }
.icon-circle { width: 55px; height: 55px; background: #f5b34d; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #000; font-size: 20px; }
.footer-box h4 { color: #fff; margin-bottom: 5px; }
.footer-main { max-width: 1200px; margin: 70px auto 40px; display: grid; grid-template-columns: 1.3fr 1fr 1fr; gap: 60px; }
.footer-logo { color: #f5b34d; font-size: 28px; margin-bottom: 15px; }
.footer-col h3 { color: #fff; margin-bottom: 20px; }
.footer-col ul { list-style: none; }
.footer-col ul li { margin-bottom: 12px; }
.footer-col ul li a { color: #aaa; text-decoration: none; transition: 0.3s; }
.footer-col ul li a:hover { color: #f5b34d; }
.socials { display: flex; gap: 12px; margin-top: 20px; }
.socials a { width: 42px; height: 42px; border-radius: 50%; border: 1px solid #f5b34d; display: flex; align-items: center; justify-content: center; color: #f5b34d; transition: 0.3s; }
.socials a:hover { background: #f5b34d; color: #000; }
.subscribe-form { position: relative; margin-top: 20px; }
.subscribe-form input { width: 100%; padding: 14px 55px 14px 20px; border-radius: 40px; border: 1px solid #333; background: transparent; color: #fff; }
.subscribe-form button { position: absolute; right: 5px; top: 50%; transform: translateY(-50%); width: 42px; height: 42px; border-radius: 50%; background: #f5b34d; border: none; cursor: pointer; }
.footer-bottom { text-align: center; padding: 20px 0; border-top: 1px solid rgba(255,255,255,0.05); font-size: 14px; color: #777; }

/* Premium Profile Styles */
.profile-card-glow {
    position: relative;
}
.profile-card-glow::before {
    content: '';
    position: absolute;
    inset: -1px;
    background: linear-gradient(135deg, #C89D66 0%, transparent 50%, #C89D66 100%);
    border-radius: 24px;
    opacity: 0.3;
    z-index: -1;
    filter: blur(20px);
}

.avatar-ring {
    position: relative;
}
.avatar-ring::before {
    content: '';
    position: absolute;
    inset: -4px;
    background: linear-gradient(135deg, #C89D66, #B8935E, #C89D66);
    border-radius: 50%;
    z-index: -1;
    animation: rotate 3s linear infinite;
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.premium-badge {
    background: linear-gradient(135deg, #C89D66 0%, #B8935E 100%);
    box-shadow: 0 4px 15px rgba(200, 157, 102, 0.4);
}

.input-field:focus {
    box-shadow: 0 0 0 3px rgba(200, 157, 102, 0.15);
}

.password-toggle {
    cursor: pointer;
    transition: all 0.2s;
}
.password-toggle:hover {
    color: #C89D66;
}
</style>
@endpush

<!-- 🎨 ULTRA PRO ACCOUNT DASHBOARD -->
<div class="min-h-screen bg-[#0a0a0a] py-12">
    <div class="max-w-6xl mx-auto px-6">

        <!-- Header -->
        <div class="mb-10">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-16 h-16 bg-[#C89D66]/10 rounded-2xl flex items-center justify-center">
                    <svg class="w-9 h-9 text-[#C89D66]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-5xl font-bold text-white">{{ __('messages.account_title') }}</h1>
                    <p class="text-gray-400 text-lg mt-1">{{ __('messages.account_subtitle') }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- LEFT SIDEBAR: Profile Card -->
            <div class="lg:col-span-1 space-y-6">

                <!-- Profile Card -->
                <div class="profile-card-glow bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-3xl shadow-2xl border border-gray-800 p-8">
                    <div class="text-center">
                        <!-- Avatar -->
                        <div class="avatar-ring relative w-36 h-36 mx-auto mb-6">
                            @if($user->profile_photo_path)
                                <img src="{{ asset('storage/' . $user->profile_photo_path) }}"
                                     alt="{{ $user->name }}"
                                     class="w-full h-full rounded-full object-cover border-4 border-[#C89D66]/40 shadow-2xl shadow-[#C89D66]/30">
                            @else
                                <div class="w-full h-full bg-gradient-to-br from-[#C89D66] to-[#B8935E] rounded-full flex items-center justify-center text-5xl font-bold text-white shadow-2xl shadow-[#C89D66]/40">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                            @endif
                        </div>

                        <h3 class="text-3xl font-bold text-white mb-2 tracking-tight">{{ $user->name }}</h3>
                        <p class="text-gray-400 text-base mb-5">{{ $user->email }}</p>

                        @if($user->phone)
                            <div class="inline-flex items-center gap-2 bg-gray-800/50 rounded-full px-4 py-2 mb-5">
                                <svg class="w-4 h-4 text-[#C89D66]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                <span class="text-gray-300 text-sm">{{ $user->phone }}</span>
                            </div>
                        @endif

                        <!-- Active Member Badge -->
                        <div class="premium-badge inline-flex items-center gap-2 rounded-full px-5 py-2.5 mt-4">
                            <div class="w-2.5 h-2.5 bg-white rounded-full animate-pulse"></div>
                            <span class="text-white font-semibold text-sm tracking-wide">{{ __('messages.active_member') }}</span>
                        </div>

                        <!-- Member Since -->
                        <div class="mt-6 pt-6 border-t border-gray-800">
                            <p class="text-gray-500 text-xs uppercase tracking-wider mb-1">{{ __('messages.member_since') }}</p>
                            <p class="text-white font-semibold text-lg">{{ $memberSince->format('F Y') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-3xl shadow-2xl border border-gray-800 p-6">
                    <h3 class="text-lg font-bold text-white mb-5 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#C89D66]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        {{ __('messages.account_stats') }}
                    </h3>
                    <div class="grid grid-cols-2 gap-3">
                        <!-- Total Bookings -->
                        <div class="bg-gradient-to-br from-[#C89D66]/10 to-[#C89D66]/5 rounded-2xl p-4 border border-[#C89D66]/20">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-[#C89D66]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                <span class="text-gray-400 text-xs font-medium">Total Bookings</span>
                            </div>
                            <p class="text-2xl font-bold text-white">{{ $totalBookings }}</p>
                        </div>

                        <!-- Completed Rentals -->
                        <div class="bg-gradient-to-br from-emerald-500/10 to-emerald-500/5 rounded-2xl p-4 border border-emerald-500/20">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-gray-400 text-xs font-medium">{{ __('messages.status_completed') }}</span>
                            </div>
                            <p class="text-2xl font-bold text-white">{{ $completedRentals }}</p>
                        </div>

                        <!-- Wishlist Cars -->
                        <div class="bg-gradient-to-br from-pink-500/10 to-pink-500/5 rounded-2xl p-4 border border-pink-500/20">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                                <span class="text-gray-400 text-xs font-medium">{{ __('messages.dashboard_wishlist') }}</span>
                            </div>
                            <p class="text-2xl font-bold text-white">{{ $wishlistCount }}</p>
                        </div>

                        <!-- Total Spent -->
                        <div class="bg-gradient-to-br from-blue-500/10 to-blue-500/5 rounded-2xl p-4 border border-blue-500/20">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-gray-400 text-xs font-medium">{{ __('messages.dashboard_total_spent') }}</span>
                            </div>
                            <p class="text-2xl font-bold text-white">{{ number_format($totalSpent, 0) }} {{ __('messages.currency') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Payment History Card -->
                <a href="{{ route('profile.payment-history') }}" class="block bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-3xl shadow-2xl border border-gray-800 p-6 hover:border-[#C89D66]/50 transition-all duration-300 hover:shadow-[#C89D66]/20 hover:transform hover:scale-[1.02] group">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 bg-gradient-to-br from-[#C89D66]/20 to-[#C89D66]/10 rounded-2xl flex items-center justify-center group-hover:from-[#C89D66]/30 group-hover:to-[#C89D66]/20 transition-all duration-300">
                            <span class="text-2xl">💳</span>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-white mb-1 group-hover:text-[#C89D66] transition-colors duration-300">{{ __('messages.payment_history_title') }}</h3>
                            <p class="text-gray-400 text-sm">{{ __('messages.payment_history_desc') }}</p>
                        </div>
                        <svg class="w-6 h-6 text-gray-500 group-hover:text-[#C89D66] group-hover:translate-x-1 transition-all duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>

            </div>

            <!-- RIGHT: Profile Edit Form -->
            <div class="lg:col-span-2">
                <div class="bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-3xl shadow-2xl border border-gray-800 overflow-hidden">

                    <!-- Header -->
                    <div class="bg-gradient-to-r from-[#C89D66]/20 to-[#C89D66]/10 px-8 py-6 border-b border-gray-800">
                        <h2 class="text-2xl font-bold text-white flex items-center gap-3">
                            <svg class="w-7 h-7 text-[#C89D66]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            {{ __('messages.edit_profile_info') }}
                        </h2>
                    </div>

                    <div class="p-8">

                        <!-- Success Message -->
                        @if (session('status') === 'profile-updated')
                            <div class="mb-8 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl p-5 flex items-center gap-4 animate-fade-in">
                                <div class="w-12 h-12 bg-emerald-500/20 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-emerald-400 font-bold">{{ __('messages.profile_updated') }}</p>
                                    <p class="text-emerald-300/70 text-sm">{{ __('messages.profile_updated_sub') }}</p>
                                </div>
                            </div>
                        @endif

                        <!-- Form -->
                        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-6">
                            @csrf
                            @method('PATCH')

                            <!-- Profile Photo Upload -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-400 mb-4">
                                    {{ __('messages.profile_photo') }}
                                </label>

                                <div class="flex flex-col sm:flex-row items-center gap-6">
                                    <!-- Preview -->
                                    <div class="relative w-32 h-32 flex-shrink-0">
                                        @if($user->profile_photo_path)
                                            <img src="{{ asset('storage/'.$user->profile_photo_path) }}"
                                                 id="photoPreview"
                                                 class="w-full h-full rounded-2xl object-cover border-2 border-[#C89D66]/30 shadow-lg shadow-[#C89D66]/20">
                                        @else
                                            <div id="photoPreview" class="w-full h-full bg-gradient-to-br from-[#C89D66] to-[#B8935E] rounded-2xl flex items-center justify-center text-4xl font-bold text-white shadow-lg shadow-[#C89D66]/30">
                                                {{ strtoupper(substr($user->name, 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Upload Button -->
                                    <div class="flex-1 w-full">
                                        <label for="profile_photo" class="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-8 py-4 bg-gradient-to-r from-[#C89D66] to-[#B8935E] hover:from-[#B8935E] hover:to-[#A8835E] text-white font-semibold rounded-xl cursor-pointer transition-all duration-300 shadow-lg shadow-[#C89D66]/30 hover:shadow-[#C89D66]/50 transform hover:scale-105">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            {{ __('messages.upload_photo') }}
                                        </label>
                                        <input type="file"
                                               name="profile_photo"
                                               id="profile_photo"
                                               accept="image/*"
                                               class="hidden"
                                               onchange="previewPhoto(event)">
                                        <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                            <span class="text-gray-500">JPG, PNG, WEBP</span>
                                            <span class="text-gray-600">•</span>
                                            <span class="text-gray-500">{{ __('messages.max_file_size', ['size' => '10MB']) }}</span>
                                        </div>

                                        @error('profile_photo')
                                            <p class="mt-2 text-red-400 text-sm">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Remove Button -->
                                    @if($user->profile_photo_path)
                                        <button type="button"
                                                onclick="removePhoto()"
                                                class="px-4 py-4 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded-xl transition border border-red-500/30 hover:border-red-500">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <!-- Full Name -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-400 mb-3">
                                    {{ __('messages.name') }}
                                </label>
                                <div class="relative">
                                    <input type="text"
                                           name="name"
                                           value="{{ old('name', $user->name) }}"
                                           required
                                           class="input-field w-full bg-gray-900 text-white rounded-xl px-5 py-4 border border-gray-700 focus:border-[#C89D66] focus:ring-2 focus:ring-[#C89D66]/20 outline-none transition pl-14">
                                    <svg class="w-5 h-5 text-gray-500 absolute left-5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                @error('name')
                                    <p class="mt-2 text-red-400 text-sm">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Email Address -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-400 mb-3">
                                    {{ __('messages.email') }}
                                </label>
                                <div class="relative">
                                    <input type="email"
                                           name="email"
                                           value="{{ old('email', $user->email) }}"
                                           required
                                           class="input-field w-full bg-gray-900 text-white rounded-xl px-5 py-4 border border-gray-700 focus:border-[#C89D66] focus:ring-2 focus:ring-[#C89D66]/20 outline-none transition pl-14">
                                    <svg class="w-5 h-5 text-gray-500 absolute left-5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                @error('email')
                                    <p class="mt-2 text-red-400 text-sm">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Phone Number -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-400 mb-3">
                                    {{ __('messages.phone') }}
                                </label>
                                <div class="relative">
                                    <input type="tel"
                                           name="phone"
                                           value="{{ old('phone', $user->phone ?? '') }}"
                                           placeholder="+212 6XX XXX XXX"
                                           class="input-field w-full bg-gray-900 text-white rounded-xl px-5 py-4 border border-gray-700 focus:border-[#C89D66] focus:ring-2 focus:ring-[#C89D66]/20 outline-none transition pl-14">
                                    <svg class="w-5 h-5 text-gray-500 absolute left-5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                </div>
                                @error('phone')
                                    <p class="mt-2 text-red-400 text-sm">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-400 mb-3">{{ __('messages.address') }}</label>
                                    <input type="text" name="address" value="{{ old('address', $user->address) }}" class="input-field w-full bg-gray-900 text-white rounded-xl px-5 py-4 border border-gray-700 focus:border-[#C89D66] focus:ring-2 focus:ring-[#C89D66]/20 outline-none transition">
                                    @error('address') <p class="mt-2 text-red-400 text-sm">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-400 mb-3">{{ __('messages.city') }}</label>
                                    <input type="text" name="city" value="{{ old('city', $user->city) }}" class="input-field w-full bg-gray-900 text-white rounded-xl px-5 py-4 border border-gray-700 focus:border-[#C89D66] focus:ring-2 focus:ring-[#C89D66]/20 outline-none transition">
                                    @error('city') <p class="mt-2 text-red-400 text-sm">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-400 mb-3">{{ __('messages.country') }}</label>
                                    <input type="text" name="country" value="{{ old('country', $user->country) }}" class="input-field w-full bg-gray-900 text-white rounded-xl px-5 py-4 border border-gray-700 focus:border-[#C89D66] focus:ring-2 focus:ring-[#C89D66]/20 outline-none transition">
                                    @error('country') <p class="mt-2 text-red-400 text-sm">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-400 mb-3">{{ __('messages.postal_code') }}</label>
                                    <input type="text" name="postal_code" value="{{ old('postal_code', $user->postal_code) }}" class="input-field w-full bg-gray-900 text-white rounded-xl px-5 py-4 border border-gray-700 focus:border-[#C89D66] focus:ring-2 focus:ring-[#C89D66]/20 outline-none transition">
                                    @error('postal_code') <p class="mt-2 text-red-400 text-sm">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div class="pt-6 border-t border-gray-800">
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-gray-900/60 border border-gray-800 rounded-2xl p-5">
                                    <div>
                                        <h3 class="text-xl font-bold text-white mb-2">{{ __('messages.driver_verification') }}</h3>
                                        <div class="flex flex-wrap items-center gap-3">
                                            <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-bold {{ $driverStatusClasses[$driverStatus] ?? $driverStatusClasses[\App\Models\CustomerProfile::STATUS_INCOMPLETE] }}">
                                                {{ $driverStatusLabels[$driverStatus] ?? ui_status('incomplete') }}
                                            </span>
                                            @if($profile?->driver_verification_submitted_at)
                                                <span class="text-gray-500 text-sm">{{ __('messages.submitted') }} {{ $profile->driver_verification_submitted_at->diffForHumans() }}</span>
                                            @endif
                                        </div>
                                        @if($profile?->isRejected() && $profile->driver_verification_rejection_reason)
                                            <p class="mt-3 text-sm text-red-300">{{ $profile->driver_verification_rejection_reason }}</p>
                                        @endif
                                    </div>
                                    <a href="{{ route('profile.driver.edit') }}"
                                       class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-[#C89D66] hover:bg-[#B8935E] text-white font-bold transition">
                                        {{ __('messages.complete_driver_profile') }}
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center justify-between pt-6 border-t border-gray-800">
                                <a href="{{ route('home') }}" class="text-gray-400 hover:text-white transition flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                    </svg>
                                    {{ __('messages.cancel') }}
                                </a>

                                <button type="submit"
                                        class="px-8 py-3.5 bg-gradient-to-r from-[#C89D66] to-[#B8935E] hover:from-[#B8935E] hover:to-[#A8835E] text-white font-bold rounded-xl transition-all duration-300 shadow-lg shadow-[#C89D66]/30 hover:shadow-[#C89D66]/50 transform hover:scale-105 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    {{ __('messages.save_changes') }}
                                </button>
                            </div>
                        </form>

                        <!-- Additional Info -->
                        <div class="mt-8 pt-8 border-t border-gray-800">
                            <div class="flex items-start gap-3 text-sm text-gray-500">
                                <svg class="w-5 h-5 text-blue-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p>{{ __('messages.data_secure') }}</p>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Password Change Section -->
                <div class="mt-8 bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-3xl shadow-2xl border border-gray-800 overflow-hidden">
                    <div class="bg-gradient-to-r from-rose-600/20 to-rose-600/10 px-8 py-6 border-b border-gray-800">
                        <h2 class="text-2xl font-bold text-white flex items-center gap-3">
                            <svg class="w-7 h-7 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            {{ __('messages.security_settings') }}
                        </h2>
                    </div>

                    <div class="p-8">

                        @if (session('status') === 'password-updated')
                            <div class="mb-6 bg-rose-500/10 border border-rose-500/30 rounded-2xl p-5 flex items-center gap-4 animate-fade-in">
                                <svg class="w-6 h-6 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div>
                                    <p class="text-rose-400 font-bold">{{ __('messages.password_updated') }}</p>
                                    <p class="text-rose-300/70 text-sm">{{ __('messages.password_updated_sub') }}</p>
                                </div>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('profile.password.update') }}" class="space-y-6">
                            @csrf
                            @method('PATCH')

                            <!-- Current Password -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-400 mb-3">
                                    {{ __('messages.current_password') }}
                                </label>
                                <div class="relative">
                                    <input type="password"
                                           name="current_password"
                                           id="current_password"
                                           required
                                           class="input-field w-full bg-gray-900 text-white rounded-xl px-5 py-4 border border-gray-700 focus:border-rose-400 focus:ring-2 focus:ring-rose-400/20 outline-none transition pr-14">
                                    <button type="button" onclick="togglePassword('current_password')" class="password-toggle absolute right-5 top-1/2 -translate-y-1/2 text-gray-500">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>
                                </div>
                                @error('current_password')
                                    <p class="mt-2 text-red-400 text-sm">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- New Password -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-400 mb-3">
                                    {{ __('messages.new_password') }}
                                </label>
                                <div class="relative">
                                    <input type="password"
                                           name="password"
                                           id="new_password"
                                           required
                                           class="input-field w-full bg-gray-900 text-white rounded-xl px-5 py-4 border border-gray-700 focus:border-rose-400 focus:ring-2 focus:ring-rose-400/20 outline-none transition pr-14">
                                    <button type="button" onclick="togglePassword('new_password')" class="password-toggle absolute right-5 top-1/2 -translate-y-1/2 text-gray-500">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>
                                </div>
                                @error('password')
                                    <p class="mt-2 text-red-400 text-sm">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Confirm Password -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-400 mb-3">
                                    {{ __('messages.confirm_password') }}
                                </label>
                                <div class="relative">
                                    <input type="password"
                                           name="password_confirmation"
                                           id="password_confirmation"
                                           required
                                           class="input-field w-full bg-gray-900 text-white rounded-xl px-5 py-4 border border-gray-700 focus:border-rose-400 focus:ring-2 focus:ring-rose-400/20 outline-none transition pr-14">
                                    <button type="button" onclick="togglePassword('password_confirmation')" class="password-toggle absolute right-5 top-1/2 -translate-y-1/2 text-gray-500">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Submit -->
                            <div class="pt-4">
                                <button type="submit"
                                        class="px-8 py-4 bg-gradient-to-r from-rose-500 to-rose-600 hover:from-rose-600 hover:to-rose-700 text-white font-bold rounded-xl shadow-lg shadow-rose-500/30 transition transform hover:scale-105 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                    {{ __('messages.update_password') }}
                                </button>
                            </div>
                        </form>

                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Photo Preview & Remove -->
<script>
function previewPhoto(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('photoPreview');
            preview.innerHTML = `<img src="${e.target.result}" class="w-full h-full rounded-2xl object-cover border-2 border-[#C89D66]/30 shadow-lg shadow-[#C89D66]/20">`;
        }
        reader.readAsDataURL(file);
    }
}

function removePhoto() {
    if (confirm('{{ __("messages.remove_photo_confirm") }}')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("profile.photo.delete") }}';
        form.innerHTML = '@csrf @method("DELETE")';
        document.body.appendChild(form);
        form.submit();
    }
}

function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.nextElementSibling;
    const icon = button.querySelector('svg');

    if (field.type === 'password') {
        field.type = 'text';
        icon.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
        `;
    } else {
        field.type = 'password';
        icon.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
        `;
    }
}

</script>

<style>
@keyframes fade-in {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in { animation: fade-in 0.5s ease-out; }
</style>

@endsection
