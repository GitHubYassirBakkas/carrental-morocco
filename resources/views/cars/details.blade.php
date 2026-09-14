@extends('layouts.app')

@section('title', $car->brand . ' ' . $car->model)

@section('content')
@php
    $lateGraceHours = setting('late_grace_minutes', config('rental.late_grace_minutes')) / 60;
    $lateFeePerHour = setting('late_fee_per_hour', config('rental.late_fee_per_hour'));
    $locationHasCoordinates = $car->location?->has_coordinates ?? false;
    $taxPercentage = (float) setting('tax_percentage', config('rental.tax_percentage', 0));
    $siteName = setting('site_name', 'Car Rental Morocco');
    $fullRefundHours = $globalCancellationPolicy['full_refund_hours'];
    $partialRefundHours = $globalCancellationPolicy['partial_refund_hours'];
    $partialRefundPercentage = $globalCancellationPolicy['partial_refund_percentage'];
    $partialRefundPercentageLabel = rtrim(rtrim(number_format($partialRefundPercentage, 2), '0'), '.');
@endphp

@if($locationHasCoordinates)
    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    @endpush
@endif

<div class="cd-page" x-data="carDetails()">

    {{-- ══════════════════════════
         HERO / NAV BAR
    ══════════════════════════ --}}
    <div class="cd-nav">
        <div class="cd-nav-inner">
            <a href="{{ route('cars.index') }}" class="cd-back">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                {{ __('messages.cars') }}
            </a>
            <div class="cd-breadcrumb">
                <span>{{ $car->brand }}</span>
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                <span class="bc-active">{{ $car->model }}</span>
            </div>
        </div>
    </div>

    <div class="cd-layout">

        {{-- ═══════════════════════════════
             LEFT COLUMN
        ═══════════════════════════════ --}}
        <div class="cd-left">

            {{-- ─── GALLERY ─── --}}
            <div class="cd-gallery">
                {{-- Main image --}}
                <div class="cd-main-img" id="mainImgWrap">
                    <img :src="currentImage" alt="{{ $car->brand }} {{ $car->model }}" id="mainImg">

                    {{-- Availability badge --}}
                    @if($car->is_available)
                        <div class="avail-badge avail-badge--yes">
                            <span class="avail-dot"></span>{{ __('messages.available_cars') }}
                        </div>
                    @else
                        <div class="avail-badge avail-badge--no">
                            <span class="avail-dot"></span>{{ __('messages.no_results') }}
                        </div>
                    @endif

                    {{-- Arrows --}}
                    <button class="gal-arrow gal-arrow--prev" @click="prevImage()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button class="gal-arrow gal-arrow--next" @click="nextImage()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>

                    {{-- Counter --}}
                    <div class="gal-counter">
                        <span x-text="activeIndex + 1"></span> / <span x-text="images.length"></span>
                    </div>
                </div>

                {{-- Thumbnails --}}
                <div class="cd-thumbs">
                    <template x-for="(img, i) in images" :key="i">
                        <button class="cd-thumb" :class="activeIndex === i ? 'cd-thumb--active' : ''" @click="setImage(i)">
                            <img :src="img" alt="">
                            <div class="cd-thumb-overlay" x-show="activeIndex !== i"></div>
                        </button>
                    </template>
                </div>
            </div>

            {{-- ─── CAR HEADER ─── --}}
            <div class="cd-card">
                <div class="cd-car-header">
                    <div>
                        <h1 class="cd-car-title">{{ $car->full_name }}</h1>
                        <div class="cd-car-meta">
                            <span class="cd-tag">{{ ui_car_type($car->type) }}</span>
                            <span class="cd-dot">•</span>
                            <span class="cd-year">{{ $car->year }}</span>
                            @if($car->location)
                                <span class="cd-dot">•</span>
                                <span class="cd-loc-sm">
                                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                                    {{ $car->location->name }}
                                </span>
                            @endif
                        </div>
                    </div>
                   <div class="cd-price-header">
    <strong>{{ number_format($car->price_per_day, 0) }}</strong>
    <span>MAD / {{ __('messages.per_day') }}</span>
</div>

@auth
    @php
        $isFavorited = auth()->user()->wishlists()
            ->where('car_id', $car->id)
            ->exists();
    @endphp

    @include('partials.wishlist-toggle', [
        'car' => $car,
        'isFavorited' => $isFavorited,
        'class' => 'wishlist-toggle-form--inline',
    ])
@endauth
                </div>

                {{-- Specs row --}}
                <div class="cd-specs">
                    <div class="cd-spec">
                        <div class="cd-spec-icon cd-spec-icon--blue">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                        </div>
                        <span class="cd-spec-label">{{ __('messages.transmission') }}</span>
                        <span class="cd-spec-val">{{ ui_transmission($car->transmission) }}</span>
                    </div>
                    <div class="cd-spec">
                        <div class="cd-spec-icon cd-spec-icon--green">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <span class="cd-spec-label">{{ __('messages.fuel_type') }}</span>
                        <span class="cd-spec-val">{{ ui_fuel_type($car->fuel_type) }}</span>
                    </div>
                    <div class="cd-spec">
                        <div class="cd-spec-icon cd-spec-icon--purple">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <span class="cd-spec-label">{{ __('messages.seats') }}</span>
                        <span class="cd-spec-val">{{ $car->seats }}</span>
                    </div>
                    <div class="cd-spec">
                        <div class="cd-spec-icon cd-spec-icon--amber">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        </div>
                        <span class="cd-spec-label">{{ __('messages.doors') }}</span>
                        <span class="cd-spec-val">{{ $car->doors }}</span>
                    </div>
                    <div class="cd-spec">
                        <div class="cd-spec-icon cd-spec-icon--rose">
                            <svg viewBox="0 0 20 20" fill="currentColor"><path d="M8 2a2 2 0 00-2 2v1H5a3 3 0 00-3 3v9a2 2 0 002 2h12a2 2 0 002-2V8a3 3 0 00-3-3h-1V4a2 2 0 00-2-2H8zm0 2h4v1H8V4z"/></svg>
                        </div>
                        <span class="cd-spec-label">{{ __('messages.luggage_bags') }}</span>
                        <span class="cd-spec-val">{{ $car->luggage }}</span>
                    </div>
                    @if($car->mileage !== null)
                    <div class="cd-spec">
                        <div class="cd-spec-icon cd-spec-icon--blue">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3a9 9 0 00-9 9m18 0a9 9 0 00-9-9m0 0v3m-7.8 9a9 9 0 0015.6 0M12 12l4-4m-9 8h10"/></svg>
                        </div>
                        <span class="cd-spec-label">{{ __('messages.mileage') }}</span>
                        <span class="cd-spec-val">{{ number_format($car->mileage) }} km</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- ─── DESCRIPTION ─── --}}
            <div class="cd-card">
                <div class="cd-section-title">
                    <span class="cd-title-bar cd-title-bar--gold"></span>
                    {{ __('messages.description') }}
                </div>
                <p class="cd-desc-text">{{ $car->description }}</p>
            </div>

            {{-- ─── FEATURES ─── --}}
            @php
                $features = is_array($car->features) ? $car->features : (is_string($car->features) ? json_decode($car->features, true) : []);
            @endphp

            @if(!empty($features))
            <div class="cd-card">
                <div class="cd-section-title">
                    <span class="cd-title-bar cd-title-bar--green"></span>
                    {{ __('messages.features') }}
                </div>
                <div class="cd-features-grid">
                    @foreach($features as $feat)
                        <div class="cd-feature-item">
                            <div class="cd-feat-icon">
                                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            </div>
                            <span>{{ $feat }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- ─── LOCATION ─── --}}
            @if($car->location)
            <div class="cd-card">
                <div class="cd-section-title">
                    <span class="cd-title-bar cd-title-bar--rose"></span>
                    {{ __('messages.pickup_location') }}
                </div>
                <div class="cd-loc-card">
                    <div class="cd-loc-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div>
                        <h4>{{ $car->location->name }}</h4>
                        <p>{{ $car->location->full_address }}</p>
                        @if($car->location->phone)
                            <span class="cd-loc-phone">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                {{ $car->location->phone }}
                            </span>
                        @endif
                    </div>
                </div>
                @if($locationHasCoordinates)
                    <div
                        id="cd-agency-map"
                        class="cd-map"
                        data-lat="{{ $car->location->latitude }}"
                        data-lng="{{ $car->location->longitude }}"
                        data-name="{{ $car->location->name }}"
                        data-address="{{ $car->location->full_address }}"
                    ></div>
                @else
                    <div class="cd-map-placeholder">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <p>{{ __('messages.map_location_not_available') }}</p>
                        <span>{{ $car->location->name }}</span>
                    </div>
                @endif
            </div>
            @endif

            {{-- ─── RENTAL POLICY ─── --}}
            <div class="cd-card">
                <div class="cd-section-title">
                    <span class="cd-title-bar cd-title-bar--amber"></span>
                    {{ __('messages.rental_policy') }}
                </div>
                <div class="cd-policies">

                    <div class="cd-policy">
                        <div class="cd-policy-icon cd-policy-icon--amber">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <div>
                            <span class="cd-policy-label">{{ __('messages.minimum_age') }}</span>
                            <strong>{{ $effectiveMinimumDriverAge }} {{ __('messages.years') }}</strong>
                        </div>
                    </div>

                    @php
                        $docs = is_array($car->required_documents) ? $car->required_documents : (is_string($car->required_documents) ? json_decode($car->required_documents, true) : []);
                    @endphp

                    @if(!empty($docs))
                    <div class="cd-policy cd-policy--full">
                        <div class="cd-policy-icon cd-policy-icon--blue">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div>
                            <span class="cd-policy-label">{{ __('messages.required_documents') }}</span>
                            <ul class="cd-docs-list">
                                @foreach($docs as $doc)
                                    <li><span></span>{{ $doc }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    @endif

                    @if($car->fuel_policy)
                    <div class="cd-policy">
                        <div class="cd-policy-icon cd-policy-icon--green">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <div>
                            <span class="cd-policy-label">{{ __('messages.fuel_policy') }}</span>
                            <strong>{{ $car->fuel_policy }}</strong>
                        </div>
                    </div>
                    @endif

                    <div class="cd-policy cd-policy--full">
                        <div class="cd-policy-icon cd-policy-icon--rose">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </div>
                        <div>
                            <span class="cd-policy-label">{{ __('messages.cancellation_policy') }}</span>
                            <dl class="cd-cancellation-policy">
                                <div>
                                    <dt>Full refund:</dt>
                                    <dd>{{ $fullRefundHours }}+ hours before pickup</dd>
                                </div>
                                <div>
                                    <dt>Partial refund:</dt>
                                    <dd>{{ $partialRefundHours }}-{{ $fullRefundHours }} hours before pickup - {{ $partialRefundPercentageLabel }}%</dd>
                                </div>
                                <div>
                                    <dt>No refund:</dt>
                                    <dd>Less than {{ $partialRefundHours }} hours before pickup</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    @if($car->security_deposit_amount)
                    <div class="cd-policy">
                        <div class="cd-policy-icon cd-policy-icon--purple">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <span class="cd-policy-label">{{ __('messages.security_deposit') }}</span>
                            <strong>{{ number_format($car->security_deposit_amount, 0) }} MAD</strong>
                        </div>
                    </div>
                    @endif

                </div>
            </div>

            {{-- ─── WHAT'S NOT COVERED ─── --}}
<div class="cd-card">
    <div class="cd-section-title">
        <span class="cd-title-bar cd-title-bar--rose"></span>
        {{ __('messages.not_covered_title') }}
    </div>

    {{-- Late Return Warning --}}
    <div class="cd-warning-box cd-warning-box--orange">
        <div class="cd-warn-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <h4>{{ __('messages.late_return_title') }}</h4>
            <p>{{ __('messages.late_return_desc', [
                'grace' => $lateGraceHours,
                'fee'   => $lateFeePerHour,
            ]) }}</p>
        </div>
    </div>

    {{-- Security Deposit Info --}}
    @if($car->security_deposit_amount)
    <div class="cd-warning-box cd-warning-box--purple">
        <div class="cd-warn-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <h4>{{ __('messages.security_deposit_title') }}</h4>
            <p>{{ __('messages.security_deposit_desc', ['amount' => number_format($car->security_deposit_amount, 0)]) }}</p>
        </div>
    </div>
    @endif

    {{-- Protection Coverage Table --}}
    <div class="cd-coverage-table">
        <div class="cd-cov-header">
            <span>{{ __('messages.coverage_type') }}</span>
            <span>{{ __('messages.coverage_basic') }}</span>
            <span>{{ __('messages.coverage_standard') }}</span>
            <span>{{ __('messages.coverage_premium') }}</span>
        </div>
        @php
        $coverageRows = [
            ['label' => __('messages.cov_theft'),      'basic' => false, 'standard' => true,  'premium' => true],
            ['label' => __('messages.cov_accident'),   'basic' => false, 'standard' => true,  'premium' => true],
            ['label' => __('messages.cov_damage'),     'basic' => false, 'standard' => false, 'premium' => true],
            ['label' => __('messages.cov_late'),       'basic' => false, 'standard' => false, 'premium' => false],
            ['label' => __('messages.cov_fuel'),       'basic' => false, 'standard' => false, 'premium' => false],
            ['label' => __('messages.cov_tires'),      'basic' => false, 'standard' => true,  'premium' => true],
        ];
        @endphp
        @foreach($coverageRows as $row)
        <div class="cd-cov-row">
            <span class="cd-cov-label">{{ $row['label'] }}</span>
            <span class="cd-cov-cell">
                @if($row['basic'])
                    <svg class="cd-cov-yes" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                @else
                    <svg class="cd-cov-no" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                @endif
            </span>
            <span class="cd-cov-cell">
                @if($row['standard'])
                    <svg class="cd-cov-yes" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                @else
                    <svg class="cd-cov-no" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                @endif
            </span>
            <span class="cd-cov-cell">
                @if($row['premium'])
                    <svg class="cd-cov-yes" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                @else
                    <svg class="cd-cov-no" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                @endif
            </span>
        </div>
        @endforeach
    </div>

</div>

            {{-- ─── REVIEWS ─── --}}
            @if($reviews->count() > 0)
            <div class="cd-card">
                <div class="cd-section-title">
                    <span class="cd-title-bar cd-title-bar--gold"></span>
                    {{ __('messages.reviews') }}
                </div>

                {{-- Rating summary --}}
                <div class="cd-rating-summary">
                    <div class="cd-rating-left">
                        <div class="cd-stars">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="cd-star {{ $i <= floor($reviewStats['average']) ? 'cd-star--on' : '' }}" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            @endfor
                        </div>
                        <span class="cd-rating-num">{{ $reviewStats['average'] }}</span>
                    </div>
                    <div class="cd-rating-right">
                        <p>{{ __('messages.reviews') }}</p>
                        <strong>{{ $reviewStats['count'] }}</strong>
                    </div>
                </div>

                {{-- Distribution bars --}}
                <div class="cd-dist">
                    @for($i = 5; $i >= 1; $i--)
                        <div class="cd-dist-row">
                            <span>{{ $i }}</span>
                            <svg viewBox="0 0 20 20" fill="currentColor" class="cd-dist-star"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            <div class="cd-dist-bar">
                                <div class="cd-dist-fill" style="width: {{ $reviewStats['count'] > 0 ? ($reviewStats['distribution'][$i] / $reviewStats['count']) * 100 : 0 }}%"></div>
                            </div>
                            <span class="cd-dist-count">{{ $reviewStats['distribution'][$i] }}</span>
                        </div>
                    @endfor
                </div>

                {{-- Individual reviews --}}
                <div class="cd-reviews-list">
                    @foreach($reviews->take(5) as $review)
                        <div class="cd-review">
                            <div class="cd-review-header">
                                <div class="cd-review-avatar">{{ strtoupper(substr($review->user->name, 0, 1)) }}</div>
                                <div class="cd-review-meta">
                                    <strong>{{ $review->user->name }}</strong>
                                    <span>{{ $review->created_at->diffForHumans() }}</span>
                                </div>
                                <div class="cd-review-stars">
                                    @for($i = 1; $i <= 5; $i++)
                                        <svg class="cd-star cd-star--sm {{ $i <= $review->rating ? 'cd-star--on' : '' }}" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    @endfor
                                </div>
                            </div>
                            <p class="cd-review-comment">"{{ $review->comment }}"</p>

                            @if($review->response)
                                <div class="cd-review-response">
                                    <div class="cd-response-header">
                                        <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd"/></svg>
                                        {{ $siteName }}
                                    </div>
                                    <p>{{ $review->response }}</p>
                                </div>
                            @endif

                            <div class="cd-verified">
                                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                {{ __('messages.verified_rental') }}
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($reviews->count() > 5)
                    <button class="cd-more-reviews">{{ __('messages.view_all_reviews', ['n' => $reviews->count()]) }}</button>
                @endif
            </div>

            @else
            <div class="cd-card cd-no-reviews">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                <h3>{{ __('messages.no_reviews_title') }}</h3>
                <p>{{ __('messages.no_reviews_desc') }}</p>
            </div>
            @endif

        </div>{{-- end cd-left --}}

        {{-- ═══════════════════════════════
             RIGHT COLUMN — BOOKING CARD
        ═══════════════════════════════ --}}
        <aside class="cd-right">
            <div class="cd-booking-card">

                <div class="cd-booking-header">
                    <h3>{{ __('messages.book_car') }}</h3>
                    <div class="cd-booking-price">
                        <strong>{{ number_format($car->price_per_day, 0) }}</strong>
                        <span>MAD / {{ __('messages.per_day') }}</span>
                    </div>
                </div>

                @auth
                    @if(auth()->user()->hasVerifiedEmail())
                        @php
                            $driverProfile = auth()->user()->customerProfile;
                        @endphp
                        @if($driverProfile?->isDriverVerified())

                        <form method="POST" action="{{ route('bookings.preview', $car) }}">
                            @csrf
                            @php session(['url.intended' => url()->current()]); @endphp

                            @if ($errors->any())
                                <div class="cd-form-error">
                                    @foreach ($errors->all() as $error)
                                        <div>{{ $error }}</div>
                                    @endforeach
                                </div>
                            @endif

                            <div class="cd-form-fields">

                                {{-- Pickup location --}}
                                <div class="cd-field">
                                    <label>{{ __('messages.pickup_location') }} <span>*</span></label>
                                    <div class="cd-select-wrap">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <select name="pickup_location_id" required>
                                            <option value="">{{ __('messages.select_location') }}</option>
                                            @php $activeLocations = \App\Models\Location::where('is_active', true)->get(); @endphp
                                            @foreach($activeLocations as $loc)
                                                <option value="{{ $loc->id }}" {{ old('pickup_location_id', $car->location_id) == $loc->id ? 'selected' : '' }}>
                                                    {{ $loc->name }} — {{ $loc->city }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                {{-- Dropoff location --}}
                                <div class="cd-field">
                                    <label>{{ __('messages.dropoff_location') }} <span>*</span></label>
                                    <div class="cd-select-wrap">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <select name="dropoff_location_id" required>
                                            <option value="">{{ __('messages.select_location') }}</option>
                                            @foreach($activeLocations as $loc)
                                                <option value="{{ $loc->id }}" {{ old('dropoff_location_id', $car->location_id) == $loc->id ? 'selected' : '' }}>
                                                    {{ $loc->name }} — {{ $loc->city }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <p class="cd-field-hint">{{ __('messages.different_dropoff_hint') }}</p>
                                </div>

                                {{-- Date range --}}
                                <div class="cd-field">
                                    <label>{{ __('messages.pickup_date') }} — {{ __('messages.return_date') }}</label>
                                    <div class="cd-input-wrap">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <input type="text" id="dateRange" placeholder="{{ __('messages.select_dates') }}" required>
                                    </div>
                                </div>

                                <input type="hidden" name="pickup_date" x-model="pickupDate">
                                <input type="hidden" name="return_date" x-model="returnDate">

                                {{-- Times --}}
                                <div class="cd-times">
                                    <div class="cd-field">
                                        <label>{{ __('messages.pickup_time') }}</label>
                                        <div class="cd-input-wrap">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <input type="time" x-model="pickupTime" name="pickup_time" required>
                                        </div>
                                    </div>
                                    <div class="cd-field">
                                        <label>{{ __('messages.return_time') }}</label>
                                        <div class="cd-input-wrap">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <input type="time" x-model="returnTime" name="return_time" required>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            {{-- Availability message --}}
                            <div x-show="availabilityMessage"
                                 :class="availabilityError ? 'cd-avail cd-avail--error' : 'cd-avail cd-avail--ok'"
                                 x-text="availabilityMessage">
                            </div>

                            {{-- Total --}}
                            <div class="cd-total-box">
                                <div class="cd-total-row">
                                    <span>{{ __('messages.total_price') }}</span>
                                    <strong x-text="formatPrice(total)"></strong>
                                </div>
                                <p class="cd-total-detail">
                                    <span x-text="rentalDays"></span> {{ __('messages.total_days') }} × {{ number_format($car->price_per_day, 0) }} MAD
                                </p>
                            </div>

                            <button type="submit"
                                    :disabled="availabilityError"
                                    :class="availabilityError ? 'cd-book-btn cd-book-btn--disabled' : 'cd-book-btn'"
                                    class="cd-book-btn">
                                {{ __('messages.book_now') }}
                                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            </button>
                        </form>

                        @else
                            @php
                                $driverStatus = $driverProfile?->driver_verification_status ?? \App\Models\CustomerProfile::STATUS_INCOMPLETE;
                            @endphp
                            <div class="cd-driver-block">
                                @if($driverStatus === \App\Models\CustomerProfile::STATUS_PENDING)
                                    <h4>{{ __('messages.driver_under_review') }}</h4>
                                    <p>{{ __('messages.driver_under_review_desc') }}</p>
                                    <span>{{ __('messages.driver_review_time') }}</span>
                                    <a href="{{ route('profile.driver.edit') }}">{{ __('messages.view_driver_profile') }}</a>
                                @elseif($driverStatus === \App\Models\CustomerProfile::STATUS_REJECTED)
                                    <h4>{{ __('messages.driver_verification_attention') }}</h4>
                                    <p>{{ __('messages.driver_rejected_desc') }}</p>
                                    @if($driverProfile?->driver_verification_rejection_reason)
                                        <div class="cd-driver-reason"><strong>{{ __('messages.reason') }}:</strong> {{ $driverProfile->driver_verification_rejection_reason }}</div>
                                    @endif
                                    <a href="{{ route('profile.driver.edit') }}">{{ __('messages.update_driver_profile') }}</a>
                                @else
                                    <h4>{{ __('messages.complete_driver_before_booking') }}</h4>
                                    <p>{{ __('messages.complete_driver_before_booking_desc') }}</p>
                                    <a href="{{ route('profile.driver.edit') }}">{{ __('messages.complete_driver_profile') }}</a>
                                @endif
                            </div>
                        @endif

                    @else
                        <div class="cd-verify-notice">
                            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            <p>{{ __('messages.verify_to_book') }}</p>
                            <a href="{{ route('verification.notice') }}">{{ __('messages.verify_email') }}</a>
                        </div>
                    @endif
                @endauth

                @guest
                    <div class="cd-guest-notice">
                        <p>{{ __('messages.login_to_book') }}</p>
                        <a href="{{ route('login') }}" class="cd-book-btn" style="display:flex;text-decoration:none;justify-content:center">
                            {{ __('messages.login') }}
                            <svg viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                        </a>
                    </div>
                @endguest

                <p class="cd-secure-note">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                    {{ __('messages.secure_payment') }}
                </p>

            </div>
        </aside>

    </div>{{-- end cd-layout --}}
</div>

{{-- ═══════════════════════ STYLES ═══════════════════════ --}}
<style>
:root {
    --gold:      #C89D66;
    --gold-dark: #B8935E;
    --bg:        #0a0a0a;
    --bg-card:   #0f0f0f;
    --bg-2:      #141414;
    --border:    #1e1e1e;
    --border-2:  #2a2a2a;
    --text:      #f0f0f0;
    --muted:     #777;
    --hint:      #444;
    --radius:    14px;
    --tr:        0.2s ease;
}

.cd-page { background: var(--bg); min-height: 100vh; }

/* Nav */
.cd-nav {
    background: #0c0c0c;
    border-bottom: 1px solid var(--border);
    position: sticky; top: 0; z-index: 100;
}
.cd-nav-inner {
    max-width: 1300px; margin: 0 auto;
    padding: 1rem 2rem;
    display: flex; align-items: center; justify-content: space-between;
}
.cd-back {
    display: flex; align-items: center; gap: 7px;
    font-size: 0.85rem; font-weight: 600;
    color: var(--muted); text-decoration: none;
    transition: color var(--tr);
}
.cd-back svg { width: 18px; height: 18px; transition: transform var(--tr); }
.cd-back:hover { color: var(--gold); }
.cd-back:hover svg { transform: translateX(-3px); }
.cd-breadcrumb {
    display: flex; align-items: center; gap: 6px;
    font-size: 0.72rem; color: var(--hint);
}
.cd-breadcrumb svg { width: 12px; height: 12px; }
.bc-active { color: var(--gold); font-weight: 600; }

/* Layout */
.cd-layout {
    max-width: 1300px; margin: 0 auto;
    padding: 2rem;
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 1.75rem;
    align-items: start;
}
@media (max-width: 1100px) { .cd-layout { grid-template-columns: 1fr; } .cd-right { order: -1; } }

/* Card base */
.cd-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.75rem;
    margin-bottom: 1.25rem;
    animation: fadeUp 0.4s ease both;
}
@keyframes fadeUp { from{opacity:0;transform:translateY(12px)} to{opacity:1;transform:none} }

.cd-section-title {
    display: flex; align-items: center; gap: 10px;
    font-size: 1.1rem; font-weight: 700; color: var(--text);
    margin-bottom: 1.25rem; letter-spacing: -0.02em;
}
.cd-title-bar { width: 3px; height: 20px; border-radius: 2px; flex-shrink: 0; }
.cd-title-bar--gold   { background: var(--gold); }
.cd-title-bar--green  { background: #4ade80; }
.cd-title-bar--rose   { background: #f43f5e; }
.cd-title-bar--amber  { background: #fbbf24; }
.cd-title-bar--blue   { background: #60a5fa; }

/* Gallery */
.cd-gallery { margin-bottom: 1.25rem; border-radius: var(--radius); overflow: hidden; border: 1px solid var(--border); background: var(--bg-card); }
.cd-main-img { position: relative; height: 480px; background: #000; overflow: hidden; }
.cd-main-img img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s ease; }
.cd-main-img:hover img { transform: scale(1.03); }
.avail-badge {
    position: absolute; top: 16px; left: 16px;
    display: flex; align-items: center; gap: 7px;
    padding: 6px 14px; border-radius: 100px;
    font-size: 0.72rem; font-weight: 700;
    letter-spacing: 0.04em;
}
.avail-badge--yes { background: rgba(74,222,128,0.15); border: 1px solid rgba(74,222,128,0.3); color: #4ade80; }
.avail-badge--no  { background: rgba(248,113,113,0.15); border: 1px solid rgba(248,113,113,0.3); color: #f87171; }
.avail-dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; animation: pulse 2s ease infinite; }
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.4} }
.gal-arrow {
    position: absolute; top: 50%; transform: translateY(-50%);
    width: 46px; height: 46px; border-radius: 50%;
    background: rgba(0,0,0,0.6); border: 1px solid #333;
    color: #fff; display: flex; align-items: center; justify-content: center;
    cursor: pointer; opacity: 0; transition: opacity var(--tr), background var(--tr);
}
.cd-main-img:hover .gal-arrow { opacity: 1; }
.gal-arrow:hover { background: rgba(200,157,102,0.3); border-color: var(--gold); }
.gal-arrow svg { width: 20px; height: 20px; }
.gal-arrow--prev { left: 14px; }
.gal-arrow--next { right: 14px; }
.gal-counter {
    position: absolute; bottom: 14px; right: 14px;
    background: rgba(0,0,0,0.75); border: 1px solid #333;
    padding: 4px 12px; border-radius: 100px;
    font-size: 0.75rem; color: #ddd;
}
.cd-thumbs { display: grid; grid-template-columns: repeat(5,1fr); gap: 8px; padding: 12px; background: #0a0a0a; }
.cd-thumb { position: relative; border-radius: 8px; overflow: hidden; border: 2px solid transparent; transition: border-color var(--tr), transform var(--tr); cursor: pointer; }
.cd-thumb img { width: 100%; height: 80px; object-fit: cover; display: block; }
.cd-thumb--active { border-color: var(--gold); box-shadow: 0 0 0 2px rgba(200,157,102,0.3); }
.cd-thumb:hover { transform: scale(1.04); }
.cd-thumb-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.5); }

/* Car header */
.cd-car-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; gap: 1rem; }
.cd-car-title { font-size: 1.8rem; font-weight: 800; color: var(--text); letter-spacing: -0.04em; margin-bottom: 8px; }
.cd-car-meta { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.cd-tag { padding: 3px 12px; border-radius: 100px; font-size: 0.7rem; font-weight: 700; background: rgba(200,157,102,0.1); border: 1px solid rgba(200,157,102,0.2); color: var(--gold); letter-spacing: 0.05em; }
.cd-year, .cd-dot { font-size: 0.8rem; color: var(--muted); }
.cd-loc-sm { display: flex; align-items: center; gap: 4px; font-size: 0.78rem; color: var(--muted); }
.cd-loc-sm svg { width: 12px; height: 12px; color: #f43f5e; }
.cd-price-header { text-align: right; flex-shrink: 0; }
.cd-price-header strong { display: block; font-size: 2rem; font-weight: 800; color: var(--gold); letter-spacing: -0.04em; }
.cd-price-header span { font-size: 0.72rem; color: var(--muted); }

/* Specs */
.cd-specs { display: grid; grid-template-columns: repeat(auto-fit,minmax(92px,1fr)); gap: 1rem; border-top: 1px solid var(--border); padding-top: 1.25rem; }
@media (max-width: 700px) { .cd-specs { grid-template-columns: repeat(3,1fr); } }
.cd-spec { display: flex; flex-direction: column; align-items: center; gap: 6px; text-align: center; }
.cd-spec-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
.cd-spec-icon svg { width: 18px; height: 18px; }
.cd-spec-icon--blue   { background: rgba(96,165,250,0.1); } .cd-spec-icon--blue   svg { color: #60a5fa; }
.cd-spec-icon--green  { background: rgba(74,222,128,0.1);  } .cd-spec-icon--green  svg { color: #4ade80; }
.cd-spec-icon--purple { background: rgba(192,132,252,0.1); } .cd-spec-icon--purple svg { color: #c084fc; }
.cd-spec-icon--amber  { background: rgba(251,191,36,0.1);  } .cd-spec-icon--amber  svg { color: #fbbf24; }
.cd-spec-icon--rose   { background: rgba(244,63,94,0.1);   } .cd-spec-icon--rose   svg { color: #f43f5e; }
.cd-spec-label { font-size: 0.65rem; color: var(--hint); text-transform: uppercase; letter-spacing: 0.06em; }
.cd-spec-val   { font-size: 0.85rem; font-weight: 700; color: var(--text); }

/* Description */
.cd-desc-text { font-size: 0.875rem; color: var(--muted); line-height: 1.8; }

/* Features */
.cd-features-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
@media (max-width: 600px) { .cd-features-grid { grid-template-columns: 1fr; } }
.cd-feature-item { display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 10px; padding: 10px 14px; transition: border-color var(--tr); }
.cd-feature-item:hover { border-color: rgba(200,157,102,0.2); }
.cd-feat-icon { width: 30px; height: 30px; border-radius: 8px; background: rgba(74,222,128,0.1); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.cd-feat-icon svg { width: 14px; height: 14px; color: #4ade80; }
.cd-feature-item span { font-size: 0.82rem; color: #ccc; font-weight: 500; }

/* Location */
.cd-loc-card { display: flex; align-items: flex-start; gap: 14px; background: rgba(244,63,94,0.05); border: 1px solid rgba(244,63,94,0.1); border-radius: 10px; padding: 1rem 1.1rem; margin-bottom: 1rem; }
.cd-loc-icon { width: 40px; height: 40px; border-radius: 10px; background: rgba(244,63,94,0.1); display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: #f43f5e; }
.cd-loc-icon svg { width: 20px; height: 20px; }
.cd-loc-card h4 { font-size: 1rem; font-weight: 700; color: var(--text); margin-bottom: 3px; }
.cd-loc-card p  { font-size: 0.8rem; color: var(--muted); margin-bottom: 6px; }
.cd-loc-phone { display: flex; align-items: center; gap: 5px; font-size: 0.75rem; color: var(--hint); }
.cd-loc-phone svg { width: 13px; height: 13px; }
.cd-map { height: 260px; width: 100%; border: 1px solid var(--border); border-radius: 10px; overflow: hidden; background: #111; }
.cd-map .leaflet-popup-content-wrapper, .cd-map .leaflet-popup-tip { background: #111520; color: var(--text); border: 1px solid var(--border); }
.cd-map .leaflet-popup-content { font-family: 'DM Sans', system-ui, sans-serif; font-size: 0.8rem; line-height: 1.5; }
.cd-map .leaflet-control-attribution { background: rgba(17,21,32,0.82); color: var(--muted); }
.cd-map .leaflet-control-attribution a { color: var(--gold); }
.cd-map-placeholder { height: 220px; background: #111; border: 1px solid var(--border); border-radius: 10px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; }
.cd-map-placeholder svg { width: 40px; height: 40px; color: var(--hint); }
.cd-map-placeholder p { font-size: 0.9rem; font-weight: 600; color: var(--muted); }
.cd-map-placeholder span { font-size: 0.75rem; color: var(--hint); }

/* Policies */
.cd-policies { display: flex; flex-direction: column; gap: 10px; }
.cd-policy { display: flex; align-items: flex-start; gap: 12px; background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 10px; padding: 12px 14px; }
.cd-policy--full { flex-direction: column; }
.cd-policy--full .cd-policy-icon { margin-bottom: 0; }
.cd-policy > div:last-child, .cd-policy--full > div:last-child { display: flex; flex-direction: column; gap: 4px; }
.cd-policy-icon { width: 36px; height: 36px; border-radius: 9px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.cd-policy-icon svg { width: 17px; height: 17px; }
.cd-policy-icon--amber  { background: rgba(251,191,36,0.1);  } .cd-policy-icon--amber  svg { color: #fbbf24; }
.cd-policy-icon--blue   { background: rgba(96,165,250,0.1);  } .cd-policy-icon--blue   svg { color: #60a5fa; }
.cd-policy-icon--green  { background: rgba(74,222,128,0.1);  } .cd-policy-icon--green  svg { color: #4ade80; }
.cd-policy-icon--rose   { background: rgba(244,63,94,0.1);   } .cd-policy-icon--rose   svg { color: #f43f5e; }
.cd-policy-icon--purple { background: rgba(192,132,252,0.1); } .cd-policy-icon--purple svg { color: #c084fc; }
.cd-policy-label { font-size: 0.72rem; color: var(--hint); text-transform: uppercase; letter-spacing: 0.06em; }
.cd-policy strong { font-size: 0.875rem; color: var(--text); font-weight: 600; }
.cd-cancellation-policy { display: flex; flex-direction: column; gap: 6px; margin: 2px 0 0; }
.cd-cancellation-policy div { display: grid; grid-template-columns: 110px 1fr; gap: 8px; align-items: baseline; }
.cd-cancellation-policy dt { font-size: 0.8rem; color: var(--text); font-weight: 700; }
.cd-cancellation-policy dd { margin: 0; font-size: 0.8rem; color: var(--muted); line-height: 1.4; }
.cd-docs-list { list-style: none; display: flex; flex-direction: column; gap: 5px; margin-top: 4px; }
.cd-docs-list li { display: flex; align-items: center; gap: 7px; font-size: 0.8rem; color: var(--muted); }
.cd-docs-list li span { width: 6px; height: 6px; border-radius: 50%; background: #60a5fa; flex-shrink: 0; }



/* ─── Warning Boxes ─── */
.cd-warning-box {
    display: flex; align-items: flex-start; gap: 12px;
    border-radius: 10px; padding: 14px 16px;
    margin-bottom: 10px;
}
.cd-warning-box--orange {
    background: rgba(251,146,60,0.07);
    border: 1px solid rgba(251,146,60,0.2);
}
.cd-warning-box--purple {
    background: rgba(192,132,252,0.07);
    border: 1px solid rgba(192,132,252,0.2);
}
.cd-warn-icon {
    width: 36px; height: 36px; border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.cd-warning-box--orange .cd-warn-icon { background: rgba(251,146,60,0.12); color: #fb923c; }
.cd-warning-box--purple .cd-warn-icon { background: rgba(192,132,252,0.12); color: #c084fc; }
.cd-warn-icon svg { width: 18px; height: 18px; }
.cd-warning-box h4 { font-size: 0.875rem; font-weight: 700; color: var(--text); margin-bottom: 3px; }
.cd-warning-box p  { font-size: 0.78rem; color: var(--muted); line-height: 1.6; }

/* ─── Coverage Table ─── */
.cd-coverage-table {
    border: 1px solid var(--border);
    border-radius: 10px;
    overflow: hidden;
    margin-top: 12px;
}
.cd-cov-header {
    display: grid; grid-template-columns: 1fr repeat(3, 80px);
    background: rgba(255,255,255,0.03);
    border-bottom: 1px solid var(--border);
    padding: 10px 14px;
}
.cd-cov-header span {
    font-size: 0.65rem; font-weight: 700;
    color: var(--hint); text-transform: uppercase;
    letter-spacing: 0.07em; text-align: center;
}
.cd-cov-header span:first-child { text-align: left; }
.cd-cov-row {
    display: grid; grid-template-columns: 1fr repeat(3, 80px);
    padding: 10px 14px;
    border-bottom: 1px solid var(--border);
    transition: background var(--tr);
}
.cd-cov-row:last-child { border-bottom: none; }
.cd-cov-row:hover { background: rgba(255,255,255,0.02); }
.cd-cov-label { font-size: 0.8rem; color: var(--muted); display: flex; align-items: center; }
.cd-cov-cell  { display: flex; align-items: center; justify-content: center; }
.cd-cov-yes { width: 18px; height: 18px; color: #4ade80; }
.cd-cov-no  { width: 16px; height: 16px; color: #444; }

/* Reviews */
.cd-rating-summary { display: flex; align-items: center; justify-content: space-between; background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 10px; padding: 1rem 1.25rem; margin-bottom: 1rem; }
.cd-rating-left { display: flex; align-items: center; gap: 10px; }
.cd-stars { display: flex; gap: 3px; }
.cd-star { width: 22px; height: 22px; color: var(--border-2); }
.cd-star--sm { width: 16px; height: 16px; }
.cd-star--on { color: #fbbf24; }
.cd-rating-num { font-size: 2rem; font-weight: 800; color: var(--text); letter-spacing: -0.04em; }
.cd-rating-right { text-align: right; }
.cd-rating-right p { font-size: 0.72rem; color: var(--hint); }
.cd-rating-right strong { font-size: 1.1rem; font-weight: 700; color: var(--text); }
.cd-dist { display: flex; flex-direction: column; gap: 6px; margin-bottom: 1.25rem; }
.cd-dist-row { display: flex; align-items: center; gap: 8px; }
.cd-dist-row > span:first-child { font-size: 0.72rem; color: var(--muted); width: 10px; text-align: right; }
.cd-dist-star { width: 12px; height: 12px; color: #fbbf24; flex-shrink: 0; }
.cd-dist-bar { flex: 1; height: 6px; background: var(--border-2); border-radius: 10px; overflow: hidden; }
.cd-dist-fill { height: 100%; background: #fbbf24; border-radius: 10px; transition: width 0.6s ease; }
.cd-dist-count { font-size: 0.7rem; color: var(--hint); width: 16px; text-align: right; }
.cd-reviews-list { display: flex; flex-direction: column; gap: 10px; }
.cd-review { background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 10px; padding: 1.1rem; transition: border-color var(--tr); }
.cd-review:hover { border-color: var(--border-2); }
.cd-review-header { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 10px; }
.cd-review-avatar { width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, #60a5fa, #c084fc); display: flex; align-items: center; justify-content: center; font-size: 0.9rem; font-weight: 700; color: #fff; flex-shrink: 0; }
.cd-review-meta { flex: 1; }
.cd-review-meta strong { display: block; font-size: 0.875rem; color: var(--text); font-weight: 600; }
.cd-review-meta span   { font-size: 0.72rem; color: var(--hint); }
.cd-review-stars { display: flex; gap: 2px; }
.cd-review-comment { font-size: 0.82rem; color: var(--muted); line-height: 1.7; margin-bottom: 10px; }
.cd-review-response { border-left: 2px solid #60a5fa; background: rgba(96,165,250,0.05); border-radius: 0 8px 8px 0; padding: 10px 12px; margin-bottom: 10px; }
.cd-response-header { display: flex; align-items: center; gap: 6px; font-size: 0.75rem; font-weight: 600; color: #60a5fa; margin-bottom: 6px; }
.cd-response-header svg { width: 14px; height: 14px; }
.cd-review-response p { font-size: 0.78rem; color: var(--muted); }
.cd-verified { display: flex; align-items: center; gap: 5px; font-size: 0.72rem; color: #4ade80; border-top: 1px solid var(--border); padding-top: 8px; }
.cd-verified svg { width: 14px; height: 14px; }
.cd-more-reviews { display: block; width: 100%; margin-top: 1rem; padding: 0.65rem; background: transparent; border: 1px solid var(--border-2); border-radius: 8px; color: var(--gold); font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all var(--tr); }
.cd-more-reviews:hover { background: rgba(200,157,102,0.08); border-color: var(--gold); }
.cd-no-reviews { text-align: center; padding: 2.5rem 1rem; }
.cd-no-reviews svg { width: 48px; height: 48px; color: var(--hint); margin: 0 auto 1rem; display: block; }
.cd-no-reviews h3 { font-size: 1.1rem; font-weight: 700; color: var(--text); margin-bottom: 5px; }
.cd-no-reviews p  { font-size: 0.82rem; color: var(--muted); }

/* ─── Booking card ─── */
.cd-booking-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; position: sticky; top: 90px; }
.cd-booking-header { display: flex; align-items: center; justify-content: space-between; padding: 1.4rem 1.5rem; border-bottom: 1px solid var(--border); background: rgba(200,157,102,0.04); }
.cd-booking-header h3 { font-size: 1.1rem; font-weight: 700; color: var(--text); }
.cd-booking-price strong { display: block; font-size: 1.4rem; font-weight: 800; color: var(--gold); letter-spacing: -0.03em; text-align: right; }
.cd-booking-price span   { font-size: 0.65rem; color: var(--hint); }
.cd-form-error { margin: 1rem 1.5rem 0; background: rgba(248,113,113,0.08); border: 1px solid rgba(248,113,113,0.2); border-radius: 8px; padding: 10px 12px; font-size: 0.78rem; color: #fca5a5; }
.cd-form-fields { padding: 1.25rem 1.5rem; display: flex; flex-direction: column; gap: 1rem; }
.cd-field label { display: block; font-size: 0.68rem; font-weight: 700; color: var(--muted); letter-spacing: 0.07em; text-transform: uppercase; margin-bottom: 5px; }
.cd-field label span { color: #f43f5e; }
.cd-field-hint { font-size: 0.65rem; color: var(--hint); margin-top: 4px; }
.cd-select-wrap, .cd-input-wrap { position: relative; }
.cd-select-wrap svg, .cd-input-wrap svg { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); width: 15px; height: 15px; color: var(--hint); pointer-events: none; }
.cd-select-wrap select,
.cd-input-wrap input {
    width: 100% !important;
    background: var(--bg-2) !important;
    background-color: var(--bg-2) !important;
    border: 1px solid var(--border-2) !important;
    border-radius: 8px !important;
    color: var(--text) !important;
    font-size: 0.85rem !important;
    padding: 0.65rem 0.75rem 0.65rem 2.2rem !important;
    outline: none !important;
    -webkit-text-fill-color: var(--text) !important;
    appearance: none; -webkit-appearance: none;
    transition: border-color var(--tr);
}
.cd-select-wrap select:-webkit-autofill,
.cd-input-wrap input:-webkit-autofill {
    -webkit-box-shadow: 0 0 0 1000px var(--bg-2) inset !important;
    -webkit-text-fill-color: var(--text) !important;
}
.cd-select-wrap select:focus,
.cd-input-wrap input:focus { border-color: var(--gold) !important; box-shadow: 0 0 0 2px rgba(200,157,102,0.12) !important; }
.cd-select-wrap::after { content:''; position:absolute; right:10px; top:50%; transform:translateY(-50%); border-left:4px solid transparent; border-right:4px solid transparent; border-top:5px solid var(--hint); pointer-events:none; }
.cd-times { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.cd-avail {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 1.5rem;
    padding: 10px 14px;
    border-radius: 8px;
    font-size: 0.82rem;
    font-weight: 600;
    line-height: 1.4;
}
.cd-avail::before {
    content: '';
    width: 8px; height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}
.cd-avail--ok {
    background: rgba(74,222,128,0.08);
    border: 1px solid rgba(74,222,128,0.2);
    color: #4ade80;
}
.cd-avail--ok::before { background: #4ade80; animation: avail-pulse 2s ease infinite; }
.cd-avail--error {
    background: rgba(248,113,113,0.08);
    border: 1px solid rgba(248,113,113,0.2);
    color: #fca5a5;
}
.cd-avail--error::before { background: #f87171; }
@keyframes avail-pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:0.4;transform:scale(0.7)} }
.cd-total-box { margin: 1rem 1.5rem; background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 10px; padding: 1rem 1.1rem; }
.cd-total-row { display: flex; align-items: center; justify-content: space-between; }
.cd-total-row span { font-size: 0.875rem; color: var(--muted); }
.cd-total-row strong { font-size: 1.3rem; font-weight: 800; color: var(--gold); letter-spacing: -0.03em; }
.cd-total-detail { font-size: 0.72rem; color: var(--hint); margin-top: 4px; }
.cd-book-btn {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    width: calc(100% - 3rem); margin: 0.85rem 1.5rem;
    padding: 0.9rem;
    background: linear-gradient(135deg, var(--gold), var(--gold-dark));
    border: none; border-radius: 10px;
    color: #fff; font-size: 0.9rem; font-weight: 700;
    cursor: pointer;
    transition: opacity var(--tr), box-shadow var(--tr), transform 0.15s;
    box-shadow: 0 4px 20px rgba(200,157,102,0.22);
}
.cd-book-btn svg { width: 16px; height: 16px; transition: transform 0.2s; }
.cd-book-btn:hover { opacity: 0.9; box-shadow: 0 6px 28px rgba(200,157,102,0.38); }
.cd-book-btn:hover svg { transform: translateX(3px); }
.cd-book-btn:active { transform: scale(0.985); }
.cd-book-btn--disabled { background: #333 !important; box-shadow: none !important; cursor: not-allowed !important; }
.cd-verify-notice, .cd-guest-notice { margin: 1.5rem; background: rgba(251,191,36,0.06); border: 1px solid rgba(251,191,36,0.2); border-radius: 10px; padding: 1.1rem; text-align: center; }
.cd-verify-notice svg { width: 22px; height: 22px; color: #fbbf24; display: block; margin: 0 auto 8px; }
.cd-verify-notice p, .cd-guest-notice p { font-size: 0.82rem; color: #fbbf24; margin-bottom: 10px; }
.cd-verify-notice a { display: inline-block; background: var(--gold); color: #fff; padding: 7px 18px; border-radius: 8px; font-size: 0.8rem; font-weight: 700; text-decoration: none; }
.cd-driver-block { margin: 1.5rem; background: rgba(200,157,102,0.06); border: 1px solid rgba(200,157,102,0.22); border-radius: 10px; padding: 1.25rem; }
.cd-driver-block h4 { color: var(--text); font-size: 1rem; font-weight: 800; margin-bottom: 0.55rem; letter-spacing: 0; }
.cd-driver-block p { color: #b8b8b8; font-size: 0.86rem; line-height: 1.6; margin-bottom: 0.9rem; }
.cd-driver-block span { display: block; color: #93c5fd; font-size: 0.78rem; margin-bottom: 1rem; }
.cd-driver-block a { display: flex; align-items: center; justify-content: center; width: 100%; background: var(--gold); color: #fff; padding: 0.8rem 1rem; border-radius: 8px; font-size: 0.86rem; font-weight: 800; text-decoration: none; }
.cd-driver-block a:hover { background: var(--gold-dark); }
.cd-driver-reason { background: rgba(248,113,113,0.1); border: 1px solid rgba(248,113,113,0.24); border-radius: 8px; color: #fecaca; font-size: 0.82rem; line-height: 1.5; margin-bottom: 1rem; padding: 0.75rem; }
.cd-driver-reason strong { color: #fee2e2; }
.cd-secure-note { display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.7rem; color: var(--hint); padding: 0 1.5rem 1.25rem; }
.cd-secure-note svg { width: 12px; height: 12px; color: #4ade80; }

/* RTL */
[dir="rtl"] .cd-back svg { transform: scaleX(-1); }
[dir="rtl"] .cd-back:hover svg { transform: scaleX(-1) translateX(3px); }
[dir="rtl"] .cd-breadcrumb svg { transform: scaleX(-1); }
[dir="rtl"] .cd-select-wrap svg, [dir="rtl"] .cd-input-wrap svg { left: auto; right: 11px; }
[dir="rtl"] .cd-select-wrap select, [dir="rtl"] .cd-input-wrap input { padding: 0.65rem 2.2rem 0.65rem 0.75rem !important; }
[dir="rtl"] .cd-select-wrap::after { right: auto; left: 10px; }
[dir="rtl"] .cd-review-response { border-left: none; border-right: 2px solid #60a5fa; border-radius: 8px 0 0 8px; }
[dir="rtl"] .cd-book-btn svg { transform: scaleX(-1); }
[dir="rtl"] .cd-book-btn:hover svg { transform: scaleX(-1) translateX(-3px); }
</style>

@push('scripts')
@if($locationHasCoordinates)
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const mapEl = document.getElementById('cd-agency-map');

    if (!mapEl || typeof L === 'undefined') {
        return;
    }

    const latitude = Number.parseFloat(mapEl.dataset.lat);
    const longitude = Number.parseFloat(mapEl.dataset.lng);

    if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
        return;
    }

    const map = L.map(mapEl, {
        scrollWheelZoom: false,
    }).setView([latitude, longitude], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    const popupContent = document.createElement('div');
    const popupName = document.createElement('strong');
    const popupAddress = document.createElement('span');

    popupName.textContent = mapEl.dataset.name || '';
    popupAddress.textContent = mapEl.dataset.address || '';
    popupContent.append(popupName, document.createElement('br'), popupAddress);

    L.marker([latitude, longitude])
        .addTo(map)
        .bindPopup(popupContent)
        .openPopup();
});
</script>
@endif
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('carDetails', () => ({
        images: @json($car->all_images),
        currentImage: '',
        activeIndex: 0,
        pickupDate: '',
        returnDate: '',
        pickupTime: '10:00',
        returnTime: '10:00',
        pricePerDay: {{ $car->price_per_day }},
        insurancePrice: {{ $insurancePrice ?? 0 }},
        taxPercentage: {{ $taxPercentage }},
        rentalDays: 1,
        total: 0,
        minDays: {{ $bookingMinDays }},
        maxDays: {{ $bookingMaxDays }},
        maxAdvanceDays: {{ $maxAdvanceBookingDays }},
        maxAdvancePickupDate: '{{ $maxAdvancePickupDate }}',
        availabilityMessage: '',
        availabilityError: false,
        msgAvailable:       '{{ __("messages.car_available") }}',
        msgNotAvailable:    '{{ __("messages.car_not_available") }}',
        msgCheckFailed:     '{{ __("messages.availability_check_failed") }}',
        msgMinDays:         '{{ __("messages.min_rental_days") }}',
        msgMaxDays:         '{{ __("messages.max_rental_days") }}',

        init() {
            if (this.images.length) this.currentImage = this.images[0];
            flatpickr("#dateRange", {
                mode: "range", minDate: "today", maxDate: "{{ $datepickerMaxDate }}", dateFormat: "Y-m-d",
                disable: @json($bookedRanges ?? []),
                onChange: (dates) => {
                    if (dates.length === 2) {
                        this.pickupDate = this.fmt(dates[0]);
                        this.returnDate = this.fmt(dates[1]);
                        this.calculateDays();
                        if (!this.availabilityError) this.checkAvailability();
                    }
                }
            });
            this.calculateDays();
        },

        fmt(d) { return flatpickr.formatDate(d, 'Y-m-d'); },

        localDate(value) {
            const [year, month, day] = value.split('-').map(Number);
            return new Date(year, month - 1, day, 12);
        },

        taxedTotal(subtotal) {
            return subtotal + (subtotal * this.taxPercentage / 100);
        },

        setImage(i)  { this.activeIndex = i; this.currentImage = this.images[i]; },
        nextImage()  { this.activeIndex = (this.activeIndex + 1) % this.images.length; this.currentImage = this.images[this.activeIndex]; },
        prevImage()  { this.activeIndex = (this.activeIndex - 1 + this.images.length) % this.images.length; this.currentImage = this.images[this.activeIndex]; },

        calculateDays() {
            if (!this.pickupDate || !this.returnDate) { this.total = this.taxedTotal(this.pricePerDay + this.insurancePrice); return; }
            if (this.localDate(this.pickupDate) > this.localDate(this.maxAdvancePickupDate)) {
                this.availabilityMessage = `Pickup must be within ${this.maxAdvanceDays} days`;
                this.availabilityError = true;
                return;
            }
            const days = Math.ceil((this.localDate(this.returnDate) - this.localDate(this.pickupDate)) / 86400000);
            this.rentalDays = Math.max(1, days);
            if (this.rentalDays < this.minDays) { this.availabilityMessage = `${this.msgMinDays} ${this.minDays}`; this.availabilityError = true; return; }
            if (this.rentalDays > this.maxDays) { this.availabilityMessage = `${this.msgMaxDays} ${this.maxDays}`; this.availabilityError = true; return; }
            this.availabilityError = false;
            this.availabilityMessage = '';
            this.total = this.taxedTotal(this.pricePerDay * this.rentalDays + this.insurancePrice);
        },

        async checkAvailability() {
            if (!this.pickupDate || !this.returnDate) return;
            try {
                const res = await fetch("{{ route('cars.checkAvailability', $car) }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ pickup_date: this.pickupDate, return_date: this.returnDate })
                });
                const data = await res.json();
                if (!data.available) {
                    this.availabilityMessage = this.msgNotAvailable;
                    this.availabilityError = true;
                } else {
                    this.availabilityMessage = this.msgAvailable;
                    this.availabilityError = false;
                }
            } catch(e) {
                this.availabilityMessage = this.msgCheckFailed;
                this.availabilityError = true;
            }
        },

        formatPrice(n) { return new Intl.NumberFormat('fr-MA', { style: 'currency', currency: 'MAD' }).format(n); }
    }));
});
</script>
@endpush

@endsection
