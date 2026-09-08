@extends('layouts.app')

@section('title', __('messages.account_title'))

@section('content')

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
                <div class="bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-3xl shadow-2xl border border-gray-800 p-6">
                    <div class="text-center">
                        <!-- Avatar -->
                        <div class="relative w-32 h-32 mx-auto mb-4">
                            @if($user->profile_photo_path)
                                <img src="{{ asset('storage/' . $user->profile_photo_path) }}"
                                     alt="{{ $user->name }}"
                                     class="w-full h-full rounded-full object-cover border-4 border-[#C89D66]/30 shadow-lg shadow-[#C89D66]/20">
                            @else
                                <div class="w-full h-full bg-gradient-to-br from-[#C89D66] to-[#B8935E] rounded-full flex items-center justify-center text-4xl font-bold text-white shadow-lg shadow-[#C89D66]/30">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                            @endif

                            <!-- Edit Badge -->
                            <div class="absolute bottom-0 right-0 w-10 h-10 bg-[#C89D66] rounded-full flex items-center justify-center border-4 border-[#0f0f0f] cursor-pointer hover:bg-[#B8935E] transition">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                        </div>

                        <h3 class="text-2xl font-bold text-white mb-1">{{ $user->name }}</h3>
                        <p class="text-gray-400 text-sm mb-4">{{ $user->email }}</p>

                        @if($user->phone)
                            <p class="text-gray-500 text-sm flex items-center justify-center gap-2 mb-4">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                {{ $user->phone }}
                            </p>
                        @endif

                        <div class="pt-4 border-t border-gray-800">
                            <div class="flex items-center justify-center gap-2 text-sm">
                                <div class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                                <span class="text-emerald-400 font-medium">{{ __('messages.active_member') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-3xl shadow-2xl border border-gray-800 p-6">
                    <h3 class="text-lg font-bold text-white mb-4">{{ __('messages.account_stats') }}</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-400 text-sm">{{ __('messages.member_since') }}</span>
                            <span class="text-white font-bold">{{ $user->created_at->format('M Y') }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-400 text-sm">{{ __('messages.profile_completion') }}</span>
                            <span class="text-[#C89D66] font-bold">{{ $user->profile_photo_path ? '100%' : '80%' }}</span>
                        </div>
                    </div>
                </div>

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
                                <label class="block text-sm font-semibold text-gray-400 mb-3">
                                    {{ __('messages.profile_photo') }}
                                </label>

                                <div class="flex items-center gap-6">
                                    <!-- Preview -->
                                    <div class="relative w-24 h-24 flex-shrink-0">
                                        @if($user->profile_photo_path)
                                            <img src="{{ asset('storage/'.$user->profile_photo_path) }}"
                                                 id="photoPreview"
                                                 class="w-full h-full rounded-2xl object-cover border-2 border-gray-700">
                                        @else
                                            <div id="photoPreview" class="w-full h-full bg-gradient-to-br from-[#C89D66] to-[#B8935E] rounded-2xl flex items-center justify-center text-3xl font-bold text-white">
                                                {{ strtoupper(substr($user->name, 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Upload Button -->
                                    <div class="flex-1">
                                        <label for="profile_photo" class="inline-flex items-center gap-2 px-6 py-3 bg-gray-900 hover:bg-gray-800 text-white font-semibold rounded-xl cursor-pointer transition border border-gray-700 hover:border-[#C89D66]">
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
                                        <p class="text-gray-500 text-xs mt-2">{{ __('messages.photo_hint') }}</p>

                                        @error('profile_photo')
                                            <p class="mt-2 text-red-400 text-sm">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Remove Button -->
                                    @if($user->profile_photo_path)
                                        <button type="button"
                                                onclick="removePhoto()"
                                                class="px-4 py-3 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded-xl transition border border-red-500/30 hover:border-red-500">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <!-- Full Name -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-400 mb-2">
                                    {{ __('messages.name') }}
                                </label>
                                <div class="relative">
                                    <input type="text"
                                           name="name"
                                           value="{{ old('name', $user->name) }}"
                                           required
                                           class="w-full bg-gray-900 text-white rounded-xl px-4 py-3.5 border border-gray-700 focus:border-[#C89D66] focus:ring-2 focus:ring-[#C89D66]/20 outline-none transition pl-12">
                                    <svg class="w-5 h-5 text-gray-500 absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                @error('name')
                                    <p class="mt-2 text-red-400 text-sm">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Email Address -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-400 mb-2">
                                    {{ __('messages.email') }}
                                </label>
                                <div class="relative">
                                    <input type="email"
                                           name="email"
                                           value="{{ old('email', $user->email) }}"
                                           required
                                           class="w-full bg-gray-900 text-white rounded-xl px-4 py-3.5 border border-gray-700 focus:border-[#C89D66] focus:ring-2 focus:ring-[#C89D66]/20 outline-none transition pl-12">
                                    <svg class="w-5 h-5 text-gray-500 absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                @error('email')
                                    <p class="mt-2 text-red-400 text-sm">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Phone Number -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-400 mb-2">
                                    {{ __('messages.phone') }}
                                </label>
                                <div class="relative">
                                    <input type="tel"
                                           name="phone"
                                           value="{{ old('phone', $user->phone ?? '') }}"
                                           placeholder="+212 6XX XXX XXX"
                                           class="w-full bg-gray-900 text-white rounded-xl px-4 py-3.5 border border-gray-700 focus:border-[#C89D66] focus:ring-2 focus:ring-[#C89D66]/20 outline-none transition pl-12">
                                    <svg class="w-5 h-5 text-gray-500 absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                </div>
                                @error('phone')
                                    <p class="mt-2 text-red-400 text-sm">{{ $message }}</p>
                                @enderror
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
                                <label class="block text-sm font-semibold text-gray-400 mb-2">
                                    {{ __('messages.current_password') }}
                                </label>
                                <input type="password"
                                       name="current_password"
                                       required
                                       class="w-full bg-gray-900 text-white rounded-xl px-4 py-3 border border-gray-700 focus:border-rose-400 focus:ring-2 focus:ring-rose-400/20 outline-none">
                                @error('current_password')
                                    <p class="mt-2 text-red-400 text-sm">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- New Password -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-400 mb-2">
                                    {{ __('messages.new_password') }}
                                </label>
                                <input type="password"
                                       name="password"
                                       required
                                       class="w-full bg-gray-900 text-white rounded-xl px-4 py-3 border border-gray-700 focus:border-rose-400 focus:ring-2 focus:ring-rose-400/20 outline-none">
                                @error('password')
                                    <p class="mt-2 text-red-400 text-sm">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Confirm Password -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-400 mb-2">
                                    {{ __('messages.confirm_password') }}
                                </label>
                                <input type="password"
                                       name="password_confirmation"
                                       required
                                       class="w-full bg-gray-900 text-white rounded-xl px-4 py-3 border border-gray-700 focus:border-rose-400 focus:ring-2 focus:ring-rose-400/20 outline-none">
                            </div>

                            <!-- Submit -->
                            <div class="pt-4">
                                <button type="submit"
                                        class="px-8 py-3 bg-gradient-to-r from-rose-500 to-rose-600 hover:from-rose-600 hover:to-rose-700 text-white font-bold rounded-xl shadow-lg shadow-rose-500/30 transition transform hover:scale-105">
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
            preview.innerHTML = `<img src="${e.target.result}" class="w-full h-full rounded-2xl object-cover border-2 border-gray-700">`;
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
</script>

<style>
@keyframes fade-in {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in { animation: fade-in 0.5s ease-out; }
</style>

@endsection