@extends('layouts.app')
@section('title', __('messages.contact_us'))

@section('content')

{{-- HERO --}}
<div class="bg-[#0a0a0a] py-16 border-b border-gray-800">
    <div class="max-w-6xl mx-auto px-6 text-center">
        <span class="inline-block text-[#C89D66] text-sm font-semibold tracking-widest uppercase mb-4">
            {{ __('messages.contact_tag') }}
        </span>
        <h1 class="text-5xl font-bold text-white mb-4">
            {{ __('messages.contact_title') }}
            <span class="text-[#C89D66]">{{ __('messages.contact_title_highlight') }}</span>
        </h1>
        <p class="text-gray-400 text-lg max-w-2xl mx-auto">
            {{ __('messages.contact_subtitle') }}
        </p>
    </div>
</div>

<div class="min-h-screen bg-[#0a0a0a] py-16">
    <div class="max-w-6xl mx-auto px-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">

            {{-- LEFT: Contact Info --}}
            <div class="lg:col-span-1 space-y-5">

                {{-- Phone --}}
                <div class="bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-3xl border border-gray-800 p-6 flex items-start gap-5 hover:border-[#C89D66]/40 transition">
                    <div class="w-14 h-14 bg-[#C89D66]/10 rounded-2xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-7 h-7 text-[#C89D66]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-white font-bold mb-1">{{ __('messages.footer_call') }}</h4>
                        <a href="tel:+212600000000" class="text-gray-400 hover:text-[#C89D66] transition text-sm">+212 6 00 00 00 00</a>
                    </div>
                </div>

                {{-- Email --}}
                <div class="bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-3xl border border-gray-800 p-6 flex items-start gap-5 hover:border-[#C89D66]/40 transition">
                    <div class="w-14 h-14 bg-[#C89D66]/10 rounded-2xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-7 h-7 text-[#C89D66]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-white font-bold mb-1">{{ __('messages.footer_write') }}</h4>
                        <a href="mailto:contact@carrental.ma" class="text-gray-400 hover:text-[#C89D66] transition text-sm">contact@carrental.ma</a>
                    </div>
                </div>

                {{-- WhatsApp --}}
                <div class="bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-3xl border border-gray-800 p-6 flex items-start gap-5 hover:border-green-500/40 transition">
                    <div class="w-14 h-14 bg-green-500/10 rounded-2xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-7 h-7 text-green-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-white font-bold mb-1">WhatsApp</h4>
                        <a href="https://wa.me/212600000000" target="_blank" class="text-gray-400 hover:text-green-400 transition text-sm">+212 6 00 00 00 00</a>
                    </div>
                </div>

                {{-- Address --}}
                <div class="bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-3xl border border-gray-800 p-6 flex items-start gap-5 hover:border-[#C89D66]/40 transition">
                    <div class="w-14 h-14 bg-[#C89D66]/10 rounded-2xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-7 h-7 text-[#C89D66]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-white font-bold mb-1">{{ __('messages.footer_address_label') }}</h4>
                        <p class="text-gray-400 text-sm">{{ __('messages.footer_address') }}</p>
                    </div>
                </div>

                {{-- Working Hours --}}
                <div class="bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-3xl border border-gray-800 p-6">
                    <h4 class="text-white font-bold mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#C89D66]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ __('messages.working_hours') }}
                    </h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-400">{{ __('messages.mon_fri') }}</span>
                            <span class="text-white font-medium">08:00 - 20:00</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">{{ __('messages.sat_sun') }}</span>
                            <span class="text-white font-medium">09:00 - 18:00</span>
                        </div>
                    </div>
                </div>

            </div>

            {{-- RIGHT: Form --}}
            <div class="lg:col-span-2">

                @auth
                {{-- ✅ USER LOGGED IN → Ticket Form --}}
                <div class="bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-3xl border border-gray-800 overflow-hidden">

                    <div class="bg-gradient-to-r from-[#C89D66]/20 to-[#C89D66]/10 px-8 py-6 border-b border-gray-800">
                        <h2 class="text-2xl font-bold text-white flex items-center gap-3">
                            <svg class="w-7 h-7 text-[#C89D66]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                            </svg>
                            {{ __('messages.submit_ticket') }}
                        </h2>
                        <p class="text-gray-400 text-sm mt-1">{{ __('messages.contact_form_subtitle') }}</p>
                    </div>

                    <div class="p-8">

                        @if(session('success'))
                            <div class="mb-6 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl p-5 flex items-center gap-4">
                                <div class="w-12 h-12 bg-emerald-500/20 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-emerald-400 font-bold">{{ __('messages.ticket_created') }}</p>
                                    <p class="text-emerald-300/70 text-sm mt-0.5">
                                        <a href="{{ route('tickets.index') }}" class="underline hover:text-emerald-300 transition">
                                            {{ __('messages.view_my_tickets') }} →
                                        </a>
                                    </p>
                                </div>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('tickets.store') }}" class="space-y-6">
                            @csrf

                            {{-- Name + Email (readonly, auto-filled) --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-400 mb-2">{{ __('messages.name') }}</label>
                                    <div class="relative">
                                        <input type="text"
                                               value="{{ auth()->user()->name }}"
                                               readonly
                                               class="w-full bg-gray-800/50 text-gray-400 rounded-xl px-4 py-3.5 border border-gray-700 outline-none cursor-not-allowed pl-12">
                                        <svg class="w-5 h-5 text-gray-600 absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-400 mb-2">{{ __('messages.email') }}</label>
                                    <div class="relative">
                                        <input type="email"
                                               value="{{ auth()->user()->email }}"
                                               readonly
                                               class="w-full bg-gray-800/50 text-gray-400 rounded-xl px-4 py-3.5 border border-gray-700 outline-none cursor-not-allowed pl-12">
                                        <svg class="w-5 h-5 text-gray-600 absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            {{-- Subject --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-400 mb-2">
                                    {{ __('messages.ticket_subject') }} <span class="text-red-400">*</span>
                                </label>
                                <input type="text"
                                       name="subject"
                                       value="{{ old('subject') }}"
                                       required
                                       placeholder="{{ __('messages.ticket_subject_placeholder') }}"
                                       class="w-full bg-gray-900 text-white rounded-xl px-4 py-3.5 border border-gray-700 focus:border-[#C89D66] focus:ring-2 focus:ring-[#C89D66]/20 outline-none transition">
                                @error('subject')
                                    <p class="mt-1 text-red-400 text-sm">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Category --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-400 mb-2">
                                    {{ __('messages.ticket_category') }} <span class="text-red-400">*</span>
                                </label>
                                <select name="category" required
                                        class="w-full bg-gray-900 text-white rounded-xl px-4 py-3.5 border border-gray-700 focus:border-[#C89D66] focus:ring-2 focus:ring-[#C89D66]/20 outline-none transition">
                                    <option value="">{{ __('messages.select_category') }}</option>
                                    <option value="booking"   {{ old('category') == 'booking'   ? 'selected' : '' }}>{{ __('messages.subject_booking') }}</option>
                                    <option value="payment"   {{ old('category') == 'payment'   ? 'selected' : '' }}>{{ __('messages.subject_payment') }}</option>
                                    <option value="complaint" {{ old('category') == 'complaint' ? 'selected' : '' }}>{{ __('messages.subject_complaint') }}</option>
                                    <option value="other"     {{ old('category') == 'other'     ? 'selected' : '' }}>{{ __('messages.subject_other') }}</option>
                                </select>
                                @error('category')
                                    <p class="mt-1 text-red-400 text-sm">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Message --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-400 mb-2">
                                    {{ __('messages.contact_message') }} <span class="text-red-400">*</span>
                                </label>
                                <textarea name="message"
                                          rows="6"
                                          required
                                          placeholder="{{ __('messages.ticket_message_placeholder') }}"
                                          class="w-full bg-gray-900 text-white rounded-xl px-4 py-3.5 border border-gray-700 focus:border-[#C89D66] focus:ring-2 focus:ring-[#C89D66]/20 outline-none transition resize-none">{{ old('message') }}</textarea>
                                @error('message')
                                    <p class="mt-1 text-red-400 text-sm">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Submit --}}
                            <div class="flex items-center justify-between pt-4 border-t border-gray-800">
                                <a href="{{ route('tickets.index') }}"
                                   class="text-[#C89D66] hover:text-[#B8935E] transition text-sm font-semibold flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    {{ __('messages.my_tickets') }}
                                </a>
                                <button type="submit"
                                        class="px-8 py-3.5 bg-gradient-to-r from-[#C89D66] to-[#B8935E] hover:from-[#B8935E] hover:to-[#A8835E] text-white font-bold rounded-xl transition-all duration-300 shadow-lg shadow-[#C89D66]/30 hover:shadow-[#C89D66]/50 transform hover:scale-105 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                    </svg>
                                    {{ __('messages.submit_ticket') }}
                                </button>
                            </div>

                        </form>
                    </div>
                </div>

                @else
                {{-- ❌ NOT LOGGED IN → Login prompt --}}
                <div class="bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-3xl border border-gray-800 overflow-hidden">
                    <div class="bg-gradient-to-r from-[#C89D66]/20 to-[#C89D66]/10 px-8 py-6 border-b border-gray-800">
                        <h2 class="text-2xl font-bold text-white flex items-center gap-3">
                            <svg class="w-7 h-7 text-[#C89D66]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                            </svg>
                            {{ __('messages.send_message') }}
                        </h2>
                    </div>
                    <div class="p-12 text-center">
                        <div class="w-24 h-24 bg-[#C89D66]/10 rounded-full flex items-center justify-center mx-auto mb-6">
                            <svg class="w-12 h-12 text-[#C89D66]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-white mb-3">{{ __('messages.login_to_contact') }}</h3>
                        <p class="text-gray-400 mb-8 max-w-sm mx-auto">{{ __('messages.login_to_contact_desc') }}</p>
                        <div class="flex items-center justify-center gap-4">
                            <a href="{{ route('login') }}"
                               class="px-8 py-3.5 bg-gradient-to-r from-[#C89D66] to-[#B8935E] text-white font-bold rounded-xl shadow-lg shadow-[#C89D66]/30 hover:shadow-[#C89D66]/50 transition transform hover:scale-105">
                                {{ __('messages.login') }}
                            </a>
                            <a href="{{ route('register') }}"
                               class="px-8 py-3.5 bg-gray-800 hover:bg-gray-700 text-white font-bold rounded-xl transition border border-gray-700">
                                {{ __('messages.register') }}
                            </a>
                        </div>
                    </div>
                </div>
                @endauth

            </div>
        </div>
    </div>
</div>

@endsection