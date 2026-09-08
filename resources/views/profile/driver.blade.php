@extends('layouts.app')

@section('title', __('messages.complete_driver_profile'))

@section('content')
@php
    $status = $profile?->driver_verification_status ?? \App\Models\CustomerProfile::STATUS_INCOMPLETE;
    $statusLabels = [
        \App\Models\CustomerProfile::STATUS_INCOMPLETE => ui_status('incomplete'),
        \App\Models\CustomerProfile::STATUS_PENDING => __('messages.status_pending_verification'),
        \App\Models\CustomerProfile::STATUS_VERIFIED => ui_status('verified'),
        \App\Models\CustomerProfile::STATUS_REJECTED => ui_status('rejected'),
    ];
    $statusClasses = [
        \App\Models\CustomerProfile::STATUS_INCOMPLETE => 'text-amber-300 bg-amber-500/10 border-amber-500/30',
        \App\Models\CustomerProfile::STATUS_PENDING => 'text-blue-300 bg-blue-500/10 border-blue-500/30',
        \App\Models\CustomerProfile::STATUS_VERIFIED => 'text-emerald-300 bg-emerald-500/10 border-emerald-500/30',
        \App\Models\CustomerProfile::STATUS_REJECTED => 'text-red-300 bg-red-500/10 border-red-500/30',
    ];
    $documents = [
        'driving_license_front' => ['label' => __('messages.driving_license_front'), 'path' => 'driving_license_front_path'],
        'driving_license_back' => ['label' => __('messages.driving_license_back'), 'path' => 'driving_license_back_path'],
        'identity_front' => ['label' => __('messages.cnie_front'), 'path' => 'identity_front_path'],
        'identity_back' => ['label' => __('messages.cnie_back'), 'path' => 'identity_back_path'],
    ];
@endphp

<div class="min-h-screen bg-[#0b0d12] py-12 px-4">
    <div class="max-w-5xl mx-auto">
        <div class="mb-8 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
            <div>
                <p class="text-[#C89D66] font-semibold uppercase tracking-wide text-sm mb-2">{{ __('messages.my_account') }}</p>
                <h1 class="text-3xl md:text-4xl font-bold text-white">{{ __('messages.complete_driver_profile') }}</h1>
                <p class="text-gray-400 mt-3 max-w-3xl">
                    {{ __('messages.driver_profile_intro') }}
                </p>
            </div>
            <a href="{{ route('profile.edit') }}" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg border border-gray-700 text-gray-300 hover:text-white hover:border-[#C89D66] transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                {{ __('messages.account_profile') }}
            </a>
        </div>

        @if (session('status') === 'driver-profile-updated')
            <div class="mb-6 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-5 py-4 text-emerald-200">
                {{ __('messages.driver_profile_saved') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-lg border border-red-500/30 bg-red-500/10 px-5 py-4 text-red-200">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="mb-6 rounded-lg border border-gray-800 bg-[#11131a] p-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-white mb-2">{{ __('messages.verification_status') }}</h2>
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-bold {{ $statusClasses[$status] ?? $statusClasses[\App\Models\CustomerProfile::STATUS_INCOMPLETE] }}">
                            {{ $statusLabels[$status] ?? ui_status('incomplete') }}
                        </span>
                        @if($profile?->driver_verification_submitted_at)
                            <span class="text-sm text-gray-500">{{ __('messages.submitted') }} {{ $profile->driver_verification_submitted_at->diffForHumans() }}</span>
                        @endif
                        @if($profile?->driver_verified_at)
                            <span class="text-sm text-gray-500">{{ __('messages.verified') }} {{ $profile->driver_verified_at->diffForHumans() }}</span>
                        @endif
                    </div>
                </div>

                @if($profile?->isPendingVerification())
                    <p class="text-blue-200 text-sm max-w-md">{{ __('messages.driver_status_pending_desc') }}</p>
                @elseif($profile?->isDriverVerified())
                    <p class="text-emerald-200 text-sm max-w-md">{{ __('messages.driver_status_verified_desc') }}</p>
                @elseif($profile?->isRejected())
                    <p class="text-red-200 text-sm max-w-md">{{ __('messages.driver_status_rejected_desc') }}</p>
                @else
                    <p class="text-amber-200 text-sm max-w-md">{{ __('messages.driver_status_incomplete_desc') }}</p>
                @endif
            </div>

            @if($profile?->isRejected() && $profile->driver_verification_rejection_reason)
                <div class="mt-5 rounded-lg border border-red-500/30 bg-red-500/10 p-4 text-red-100">
                    <p class="text-sm font-semibold text-red-200 mb-1">{{ __('messages.rejection_reason') }}</p>
                    <p>{{ $profile->driver_verification_rejection_reason }}</p>
                </div>
            @endif
        </div>

        <form method="POST" action="{{ route('profile.driver.update') }}" enctype="multipart/form-data" class="rounded-lg border border-gray-800 bg-[#11131a] p-6">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="date_of_birth" class="block text-sm font-semibold text-gray-300 mb-2">{{ __('messages.date_of_birth') }}</label>
                    <input id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth', optional($profile?->date_of_birth)->toDateString()) }}" max="{{ now()->subDay()->toDateString() }}" class="w-full rounded-lg border border-gray-700 bg-gray-900 px-4 py-3 text-white outline-none focus:border-[#C89D66]">
                    @error('date_of_birth') <p class="mt-2 text-sm text-red-300">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="driving_license_number" class="block text-sm font-semibold text-gray-300 mb-2">{{ __('messages.driving_license_number') }}</label>
                    <input id="driving_license_number" type="text" name="driving_license_number" value="{{ old('driving_license_number', $profile?->driving_license_number) }}" class="w-full rounded-lg border border-gray-700 bg-gray-900 px-4 py-3 text-white outline-none focus:border-[#C89D66]">
                    @error('driving_license_number') <p class="mt-2 text-sm text-red-300">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="driving_license_country" class="block text-sm font-semibold text-gray-300 mb-2">{{ __('messages.licence_country') }} <span class="text-gray-500 font-normal">{{ __('messages.optional') }}</span></label>
                    <input id="driving_license_country" type="text" name="driving_license_country" value="{{ old('driving_license_country', $profile?->driving_license_country) }}" class="w-full rounded-lg border border-gray-700 bg-gray-900 px-4 py-3 text-white outline-none focus:border-[#C89D66]">
                    @error('driving_license_country') <p class="mt-2 text-sm text-red-300">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="driving_license_issue_date" class="block text-sm font-semibold text-gray-300 mb-2">{{ __('messages.issue_date') }} <span class="text-gray-500 font-normal">{{ __('messages.optional') }}</span></label>
                    <input id="driving_license_issue_date" type="date" name="driving_license_issue_date" value="{{ old('driving_license_issue_date', optional($profile?->driving_license_issue_date)->toDateString()) }}" max="{{ now()->toDateString() }}" class="w-full rounded-lg border border-gray-700 bg-gray-900 px-4 py-3 text-white outline-none focus:border-[#C89D66]">
                    @error('driving_license_issue_date') <p class="mt-2 text-sm text-red-300">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="driving_license_expiry_date" class="block text-sm font-semibold text-gray-300 mb-2">{{ __('messages.expiry_date') }} <span class="text-gray-500 font-normal">{{ __('messages.optional') }}</span></label>
                    <input id="driving_license_expiry_date" type="date" name="driving_license_expiry_date" value="{{ old('driving_license_expiry_date', optional($profile?->driving_license_expiry_date)->toDateString()) }}" class="w-full rounded-lg border border-gray-700 bg-gray-900 px-4 py-3 text-white outline-none focus:border-[#C89D66]">
                    @error('driving_license_expiry_date') <p class="mt-2 text-sm text-red-300">{{ $message }}</p> @enderror
                </div>

                <div class="rounded-lg border border-gray-800 bg-gray-900/60 px-4 py-3">
                    <p class="text-sm font-semibold text-gray-300 mb-1">{{ __('messages.calculated_age') }}</p>
                    <p class="text-white">{{ $profile?->age ? $profile->age . ' ' . __('messages.years') : __('messages.not_provided') }}</p>
                </div>
            </div>

            <div class="mt-8 border-t border-gray-800 pt-6">
                <h2 class="text-xl font-bold text-white mb-4">{{ __('messages.documents') }}</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($documents as $input => $document)
                        <label for="{{ $input }}" class="block rounded-lg border border-gray-700 bg-gray-900/70 p-4 hover:border-[#C89D66]/60 transition cursor-pointer">
                            <div class="flex items-center justify-between gap-3 mb-2">
                                <span class="font-semibold text-white">{{ $document['label'] }}</span>
                                @if($profile?->{$document['path']})
                                    <span class="rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-xs font-bold text-emerald-300">{{ __('messages.uploaded') }}</span>
                                @else
                                    <span class="rounded-full border border-red-500/30 bg-red-500/10 px-3 py-1 text-xs font-bold text-red-300">{{ __('messages.missing') }}</span>
                                @endif
                            </div>
                            <p class="mb-3 text-xs text-gray-500">{{ __('messages.driver_file_hint') }}</p>
                            <span class="text-sm text-[#C89D66] break-words" data-driver-file-label="{{ $input }}">{{ __('messages.no_replacement_selected') }}</span>
                            <input id="{{ $input }}" name="{{ $input }}" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf" class="hidden driver-document-input">
                            @error($input) <p class="mt-2 text-sm text-red-300">{{ $message }}</p> @enderror
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="mt-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-t border-gray-800 pt-6">
                <p class="text-sm text-gray-500">{{ __('messages.driver_required_fields_hint') }}</p>
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#C89D66] px-6 py-3 font-bold text-white hover:bg-[#B8935E] transition">
                    {{ __('messages.save_driver_profile') }}
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.querySelectorAll('.driver-document-input').forEach((input) => {
        input.addEventListener('change', () => {
            const label = document.querySelector(`[data-driver-file-label="${input.id}"]`);

            if (label) {
                label.textContent = input.files.length ? input.files[0].name : @js(__('messages.no_replacement_selected'));
            }
        });
    });
</script>
@endsection
