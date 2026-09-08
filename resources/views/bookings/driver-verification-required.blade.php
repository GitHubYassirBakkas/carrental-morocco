@extends('layouts.app')

@section('title', __('messages.driver_verification_required'))

@section('content')
@php
    $isRejected = $status === \App\Models\CustomerProfile::STATUS_REJECTED;
    $isPending = $status === \App\Models\CustomerProfile::STATUS_PENDING;
    $heading = match ($status) {
        \App\Models\CustomerProfile::STATUS_PENDING => __('messages.verification_in_progress'),
        \App\Models\CustomerProfile::STATUS_REJECTED => __('messages.verification_needs_attention'),
        default => __('messages.complete_driver_profile'),
    };
    $body = match ($status) {
        \App\Models\CustomerProfile::STATUS_PENDING => __('messages.verification_in_progress_desc'),
        \App\Models\CustomerProfile::STATUS_REJECTED => __('messages.verification_needs_attention_desc'),
        default => __('messages.driver_verification_required_desc'),
    };
    $cta = match ($status) {
        \App\Models\CustomerProfile::STATUS_PENDING => __('messages.view_driver_profile'),
        \App\Models\CustomerProfile::STATUS_REJECTED => __('messages.update_driver_profile'),
        default => __('messages.complete_driver_profile'),
    };
@endphp

<div class="min-h-screen bg-[#0b0d12] px-4 py-16">
    <div class="mx-auto max-w-3xl">
        <div class="rounded-lg border border-gray-800 bg-[#11131a] p-8 shadow-2xl shadow-black/30">
            <div class="mb-6 inline-flex h-14 w-14 items-center justify-center rounded-lg bg-[#C89D66]/15 text-[#C89D66]">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75L11.25 15 15 9.75M12 3l7 3v5c0 4.5-2.9 8.5-7 10-4.1-1.5-7-5.5-7-10V6l7-3z"/>
                </svg>
            </div>

            <h1 class="mb-4 text-3xl font-bold text-white md:text-4xl">{{ $heading }}</h1>
            <p class="text-lg leading-8 text-gray-300">{{ $body }}</p>

            @if($isPending)
                <p class="mt-4 rounded-lg border border-blue-500/30 bg-blue-500/10 px-4 py-3 text-sm text-blue-100">
                    {{ __('messages.driver_review_time_email') }}
                </p>
            @endif

            @if($isRejected && $profile?->driver_verification_rejection_reason)
                <div class="mt-5 rounded-lg border border-red-500/30 bg-red-500/10 p-4">
                    <p class="mb-1 text-sm font-semibold text-red-200">{{ __('messages.reason') }}</p>
                    <p class="text-red-100">{{ $profile->driver_verification_rejection_reason }}</p>
                </div>
            @endif

            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('profile.driver.edit') }}" class="inline-flex items-center justify-center rounded-lg bg-[#C89D66] px-6 py-3 font-bold text-white transition hover:bg-[#B8935E]">
                    {{ $cta }}
                </a>
                <a href="{{ route('cars.index') }}" class="inline-flex items-center justify-center rounded-lg border border-gray-700 px-6 py-3 font-bold text-gray-300 transition hover:border-[#C89D66] hover:text-white">
                    {{ __('messages.back_to_cars') }}
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
