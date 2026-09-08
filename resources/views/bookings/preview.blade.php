@extends('layouts.app')

@section('content')
@push('styles')
<style>

.footer {
background: #0b0d12; 
color: #ccc;
padding-top: 60px;
} 
.footer-top{
max-width: 1200px; 
margin: auto;
background: #11131a; 
border-radius: 18px;
padding: 35px; 
display: grid; 
grid-template-columns: repeat(3, 1fr); 
gap: 30px; 
}
.footer-box 
{ 
 display: flex;
 align-items: center; 
 gap: 15px; 
}
.icon-circle 
{
width: 55px; 
height: 55px;
background: #f5b34d;
border-radius: 50%;
display: flex; 
align-items: center; 
justify-content: center;
color: #000; 
font-size: 20px; 
 } 
.footer-box h4 
{ 
color: #fff; 
margin-bottom: 5px;
}
/* MAIN FOOTER */ 
.footer-main {
max-width: 1200px;
margin: 70px auto 40px;
display: grid; grid-template-columns: 1.3fr 1fr 1fr;
gap: 60px; 
} 
.footer-logo {
 color: #f5b34d; 
font-size: 28px;
margin-bottom: 15px; 
} 
.footer-col h3
{
color: #fff; 
margin-bottom: 20px; 
}
.footer-col ul 
{ 
list-style: none;
 } 
  .footer-col ul li 
 { 
margin-bottom: 12px;
}
.footer-col ul li a 
{ 
 color: #aaa; 
 text-decoration: none;
 transition: 0.3s; 
}
.footer-col ul li a:hover 
{
 color: #f5b34d; 
 } 
/* SOCIAL */
.socials
{
 display: flex; 
 gap: 12px; 
 margin-top: 20px;
}
.socials a 
{
 width: 42px;
 height: 42px; 
border-radius: 50%;
border: 1px solid #f5b34d;
display: flex; 
align-items: center; 
justify-content: center;
color: #f5b34d;
transition: 0.3s;
}
.socials a:hover 
{ 
background: #f5b34d;
color: #000; 
}
 /* SUBSCRIBE */ 
.subscribe-form 
{ 
position: relative;
margin-top: 20px; 
 } 
.subscribe-form input 
{
width: 100%;
padding: 14px 55px 14px 20px;
border-radius: 40px;
border: 1px solid #333; 
background: transparent;
color: #fff; 
} 
.subscribe-form button 
{ 
position: absolute; 
right: 5px;
top: 50%;
transform: translateY(-50%);
width: 42px;
height: 42px; 
border-radius: 50%;
background: #f5b34d;
border: none;
cursor: pointer; 
} 
.footer-bottom 
{
text-align: center; 
padding: 20px 0; 
border-top: 1px solid rgba(255,255,255,0.05);
font-size: 14px; 
color: #777; 
}
</style>
@endpush


<div class="bp-page">

    {{-- ══════════ HERO ══════════ --}}
    <div class="bp-hero">
        <div class="bp-hero-inner">
            <div class="bp-hero-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <h1>{{ __('messages.review_booking_title') }}</h1>
                <p>{{ __('messages.review_booking_subtitle') }}</p>
            </div>
        </div>

        {{-- Progress steps --}}
        <div class="bp-steps">
            <div class="bstep bstep--done"><div class="bstep-dot"><svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></div><span>{{ __('messages.step_2_title') }}</span></div>
            <div class="bstep-line bstep-line--done"></div>
            <div class="bstep bstep--done"><div class="bstep-dot"><svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></div><span>{{ __('messages.insurance') }}</span></div>
            <div class="bstep-line bstep-line--done"></div>
            <div class="bstep bstep--active"><div class="bstep-dot">3</div><span>{{ __('messages.review_step') }}</span></div>
            <div class="bstep-line"></div>
            <div class="bstep"><div class="bstep-dot">4</div><span>{{ __('messages.payment') }}</span></div>
        </div>
    </div>

    <div class="bp-content">

        {{-- ══════════ CAR ══════════ --}}
        <div class="bp-card">
            <div class="bp-card-header bp-card-header--blue">
                <div class="bp-card-hicon bp-card-hicon--blue">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/><path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/></svg>
                </div>
                <h2>{{ __('messages.car_details') }}</h2>
            </div>

            <div class="bp-car-body">
                <div class="bp-car-img">
                     <img src="{{ asset('storage/cars/'.$car->image) }}" alt="{{ $car->brand }} {{ $car->model }}">
                </div>
                <div class="bp-car-info">
                    <h3>{{ $car->brand }} {{ $car->model }}</h3>
                    <p class="bp-car-meta">{{ $car->year }} • {{ ucfirst($car->type) }}</p>

                    <div class="bp-car-specs">
                        <div class="bp-spec-pill">
                            <span>{{ __('messages.transmission') }}</span>
                            <strong>{{ ucfirst($car->transmission) }}</strong>
                        </div>
                        <div class="bp-spec-pill">
                            <span>{{ __('messages.fuel_type') }}</span>
                            <strong>{{ ucfirst($car->fuel_type) }}</strong>
                        </div>
                        <div class="bp-spec-pill">
                            <span>{{ __('messages.seats') }}</span>
                            <strong>{{ $car->seats }}</strong>
                        </div>
                        <div class="bp-spec-pill">
                            <span>{{ __('messages.mileage') }}</span>
                            <strong>{{ $car->luggage }}</strong>
                        </div>
                    </div>

                    <div class="bp-daily-rate">
                        <strong>{{ number_format($preview['car_price'] ?? 0, 0) }} MAD</strong>
                        <span>/ {{ __('messages.per_day') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════ RENTAL DATES ══════════ --}}
        <div class="bp-card">
            <div class="bp-card-header bp-card-header--purple">
                <div class="bp-card-hicon bp-card-hicon--purple">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <h2>{{ __('messages.rental_period') }}</h2>
            </div>

            <div class="bp-dates">
                <div class="bp-date-card bp-date-card--pickup">
                    <div class="bp-date-label">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                        {{ __('messages.pickup_date') }}
                    </div>
                    <strong class="bp-date-val">{{ \Carbon\Carbon::parse($preview['pickup_date'] ?? now())->format('d M Y') }}</strong>
                    <span class="bp-date-time">{{ __('messages.pickup_time') }}: {{ $preview['pickup_time'] ?? '--:--' }}</span>
                </div>

                <div class="bp-date-arrow">
                    <div class="bp-days-badge">{{ $preview['days'] ?? 0 }} {{ __('messages.total_days') }}</div>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </div>

                <div class="bp-date-card bp-date-card--return">
                    <div class="bp-date-label">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                        {{ __('messages.return_date') }}
                    </div>
                    <strong class="bp-date-val">{{ \Carbon\Carbon::parse($preview['return_date'] ?? now())->format('d M Y') }}</strong>
                    <span class="bp-date-time">{{ __('messages.return_time') }}: {{ $preview['return_time'] ?? '--:--' }}</span>
                </div>
            </div>
        </div>

        {{-- ══════════ LOCATIONS ══════════ --}}
        <div class="bp-card">
            <div class="bp-card-header bp-card-header--rose">
                <div class="bp-card-hicon bp-card-hicon--rose">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <h2>{{ __('messages.pickup_location') }} & {{ __('messages.dropoff_location') }}</h2>
            </div>

            <div class="bp-locs">
                {{-- Pickup --}}
                <div class="bp-loc bp-loc--pickup">
                    <div class="bp-loc-badge bp-loc-badge--blue">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                        {{ __('messages.pickup_location') }}
                    </div>
                    <h3>{{ $pickupLocation->name }}</h3>
                    <p>{{ $pickupLocation->address }}</p>
                    <span>{{ $pickupLocation->city }}, {{ $pickupLocation->country }}</span>
                    @if($pickupLocation->phone)
                        <div class="bp-loc-detail"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>{{ $pickupLocation->phone }}</div>
                    @endif
                    @if($pickupLocation->opening_time && $pickupLocation->closing_time)
                        <div class="bp-loc-detail bp-loc-detail--green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ substr($pickupLocation->opening_time,0,5) }} — {{ substr($pickupLocation->closing_time,0,5) }}</div>
                    @endif
                </div>

                {{-- Dropoff --}}
                <div class="bp-loc bp-loc--dropoff">
                    <div class="bp-loc-badge bp-loc-badge--green">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                        {{ __('messages.dropoff_location') }}
                    </div>
                    <h3>{{ $dropoffLocation->name }}</h3>
                    <p>{{ $dropoffLocation->address }}</p>
                    <span>{{ $dropoffLocation->city }}, {{ $dropoffLocation->country }}</span>
                    @if($dropoffLocation->phone)
                        <div class="bp-loc-detail"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>{{ $dropoffLocation->phone }}</div>
                    @endif
                    @if($dropoffLocation->opening_time && $dropoffLocation->closing_time)
                        <div class="bp-loc-detail bp-loc-detail--green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ substr($dropoffLocation->opening_time,0,5) }} — {{ substr($dropoffLocation->closing_time,0,5) }}</div>
                    @endif
                </div>
            </div>

            @if(($preview['pickup_location_id'] ?? null) != ($preview['dropoff_location_id'] ?? null))
                <div class="bp-diff-notice">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    <div>
                        <strong>{{ __('messages.different_dropoff_title') }}</strong>
                        <p>{{ __('messages.different_dropoff_title_desc') }} <span>{{ number_format($preview['dropoff_fee'] ?? 0, 0) }} MAD</span></p>
                    </div>
                </div>
            @endif
        </div>

        {{-- ══════════ INSURANCE ══════════ --}}
        <div class="bp-card">
            <div class="bp-card-header bp-card-header--green">
                <div class="bp-card-hicon bp-card-hicon--green">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                </div>
                <h2>{{ __('messages.insurance') }}</h2>
            </div>

            <div style="padding:1.5rem 1.75rem">
                @if($insurance)
                    <div class="bp-insurance-row">
                        <div class="bp-ins-icon">
                            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        </div>
                        <div class="bp-ins-info">
                            <h3>{{ $insurance->name }}</h3>
                            <p>{{ __('messages.insurance_choose_desc') }}</p>
                        </div>
                        <div class="bp-ins-price">
                            <strong>{{ number_format($preview['insurance_price'] ?? 0, 0) }} MAD</strong>
                            
                        </div>
                    </div>
                @else
                    <div class="bp-no-ins">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        {{ __('messages.no_insurance') }}
                    </div>
                @endif
            </div>
        </div>

{{-- ══════════ IMPORTANT NOTICE ══════════ --}}
<div class="bp-card">
    <div class="bp-card-header bp-card-header--gold">
        <div class="bp-card-hicon bp-card-hicon--gold">
            <svg viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
        </div>
        <h2>{{ __('messages.important_notice') }}</h2>
    </div>

    <div style="padding: 1.5rem;">
        <div class="bp-notice-grid">

            {{-- Deposit --}}
            @if($car->deposit_amount)
            <div class="bp-notice-item bp-notice-item--purple">
                <div class="bp-notice-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h4>{{ __('messages.deposit_title') }}</h4>
                    <p>{{ __('messages.deposit_review_desc', ['amount' => number_format($car->deposit_amount, 0)]) }}</p>
                    <span class="bp-notice-amount">{{ number_format($car->deposit_amount, 0) }} MAD</span>
                </div>
            </div>
            @endif

            {{-- Late Return --}}
            <div class="bp-notice-item bp-notice-item--orange">
                <div class="bp-notice-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h4>{{ __('messages.late_return_title') }}</h4>
                    <p>{{ __('messages.late_return_desc', [
                        'grace' => \App\Models\Booking::GRACE_MINUTES / 60,
                        'fee'   => \App\Models\Booking::HOURLY_LATE_FEE,
                    ]) }}</p>
                    <span class="bp-notice-amount">{{ \App\Models\Booking::HOURLY_LATE_FEE }} MAD / {{ __('messages.per_hour') }}</span>
                </div>
            </div>

            {{-- Fuel --}}
            <div class="bp-notice-item bp-notice-item--blue">
                <div class="bp-notice-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div>
                    <h4>{{ __('messages.cov_fuel') }}</h4>
                    <p>{{ __('messages.fuel_not_covered_desc') }}</p>
                </div>
            </div>

            {{-- Damage (depends on insurance) --}}
            <div class="bp-notice-item {{ $insurance && str_contains(strtolower($insurance->name ?? ''), 'premium') ? 'bp-notice-item--green' : 'bp-notice-item--red' }}">
                <div class="bp-notice-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h4>{{ __('messages.cov_damage') }}</h4>
                    @if($insurance && str_contains(strtolower($insurance->name ?? ''), 'premium'))
                        <p>{{ __('messages.damage_covered_desc') }}</p>
                        <span class="bp-notice-badge bp-notice-badge--green">✓ {{ __('messages.covered') }}</span>
                    @else
                        <p>{{ __('messages.damage_not_covered_desc') }}</p>
                        <span class="bp-notice-badge bp-notice-badge--red">✗ {{ __('messages.not_covered') }}</span>
                    @endif
                </div>
            </div>

        </div>

        {{-- Acknowledgement checkbox --}}
        <div class="bp-acknowledge">
            <label class="bp-ack-label">
                <input type="checkbox" id="ackCheck" required onchange="toggleConfirm(this)">
                <span class="bp-ack-box">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                </span>
                <span class="bp-ack-text">{{ __('messages.acknowledge_terms') }}</span>
            </label>
        </div>
    </div>
</div>

        {{-- ══════════ PRICE SUMMARY ══════════ --}}
        <div class="bp-card">
            <div class="bp-card-header bp-card-header--gold">
                <div class="bp-card-hicon bp-card-hicon--gold">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/></svg>
                </div>
                <h2>{{ __('messages.total_price') }}</h2>
            </div>

            <div class="bp-summary-body">

                {{-- Line items --}}
                <div class="bp-lines">
                    <div class="bp-line">
                        <span>{{ __('messages.price_per_day') }} × {{ $preview['days'] ?? 0 }} {{ __('messages.total_days') }}</span>
                        <strong>{{ number_format($preview['car_total'] ?? 0, 0) }} MAD</strong>
                    </div>
                    <div class="bp-line">
                        <span>{{ __('messages.insurance') }}</span>
                        <strong>{{ number_format($preview['insurance_total'] ?? 0, 0) }} MAD</strong>
                    </div>
                    @if(($preview['dropoff_fee'] ?? 0) > 0)
                        <div class="bp-line bp-line--orange">
                            <span>{{ __('messages.dropoff_fee') }}</span>
                            <strong>{{ number_format($preview['dropoff_fee'] ?? 0, 0) }} MAD</strong>
                        </div>
                    @endif
                </div>

                {{-- Coupon --}}
                <div class="bp-coupon-section">
                    <form method="POST" action="{{ route('coupons.apply') }}" id="couponForm">
                        @csrf
                        <label class="bp-coupon-label">{{ __('messages.coupon_label') }}</label>
                        <div class="bp-coupon-row">
                            <div class="bp-coupon-input-wrap">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>
                                <input type="text" name="coupon_code" id="couponCode"
                                       value="{{ session('applied_coupon.code') ?? '' }}"
                                       placeholder="{{ __('messages.coupon_placeholder') }}"
                                       {{ session('applied_coupon') ? 'readonly' : '' }}>
                            </div>
                            @if(session('applied_coupon'))
                                <button type="submit" name="action" value="remove" class="bp-coupon-btn bp-coupon-btn--remove">
                                    {{ __('messages.remove_coupon') }}
                                </button>
                            @else
                                <button type="submit" name="action" value="apply" class="bp-coupon-btn bp-coupon-btn--apply">
                                    {{ __('messages.apply_coupon') }}
                                </button>
                            @endif
                        </div>

                        @if($errors->has('coupon_code'))
                            <p class="bp-coupon-error">
                                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                {{ $errors->first('coupon_code') }}
                            </p>
                        @endif

                        @if(session('applied_coupon'))
                            <div class="bp-coupon-success">
                                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                <div>
                                    <strong>{{ __('messages.coupon_applied') }}</strong>
                                    <p>{{ session('applied_coupon.code') }} —
                                        @if(session('applied_coupon.discount_type') == 'percentage')
                                            {{ session('applied_coupon.discount_value') }}% OFF
                                        @else
                                            {{ number_format(session('applied_coupon.discount_value'), 0) }} MAD OFF
                                        @endif
                                    </p>
                                </div>
                            </div>
                        @endif
                    </form>
                </div>

                {{-- Discount line --}}
                @if(session('applied_coupon'))
                    <div class="bp-lines">
                        <div class="bp-line">
                            <span>{{ __('messages.total_price') }}</span>
                            <strong>{{ number_format($preview['grand_total'] ?? 0, 0) }} MAD</strong>
                        </div>
                        <div class="bp-line bp-line--green">
                            <span>{{ __('messages.coupon_discount') }} ({{ session('applied_coupon.code') }})</span>
                            <strong>−{{ number_format(session('coupon_discount', 0), 0) }} MAD</strong>
                        </div>
                    </div>
                @endif

                {{-- Grand total --}}
                <div class="bp-grand-total">
                    <div class="bp-grand-glow"></div>
                    <div class="bp-grand-inner">
                        <span>{{ __('messages.total_price') }}</span>
                        <strong>{{ number_format($preview['grand_total'] ?? 0, 0) }} MAD</strong>
                    </div>
                    @if(session('applied_coupon'))
                        <p class="bp-saved">🎉 {{ __('messages.you_saved') }} {{ number_format(session('coupon_discount', 0), 0) }} MAD</p>
                    @endif
                </div>

                {{-- Actions --}}
                <div class="bp-actions">
                    <form method="POST" action="{{ url('/booking/confirm') }}">
                        @csrf
                        <button type="submit" class="bp-confirm-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ __('messages.confirm_and_pay') }}
                        </button>
                    </form>

                    <a href="{{ route('cars.details', $car) }}" class="bp-back-link">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        {{ __('messages.edit_booking') }}
                    </a>
                </div>

                <div class="bp-secure">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                    {{ __('messages.secure_payment') }}
                </div>
            </div>
        </div>

    </div>{{-- end bp-content --}}
</div>
<script>
function toggleConfirm(checkbox) {
    const btn = document.querySelector('.bp-confirm-btn');
    if (btn) {
        btn.disabled = !checkbox.checked;
        btn.style.opacity = checkbox.checked ? '1' : '0.4';
        btn.style.cursor  = checkbox.checked ? 'pointer' : 'not-allowed';
    }
}
// Disable confirm button by default
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.querySelector('.bp-confirm-btn');
    if (btn) { btn.disabled = true; btn.style.opacity = '0.4'; btn.style.cursor = 'not-allowed'; }
});
</script>
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

.bp-page { background: var(--bg); min-height: 100vh; }

/* Hero */
.bp-hero {
    background: linear-gradient(135deg, #0d0d0d, #111008);
    border-bottom: 1px solid var(--border);
    padding: 2rem 0 1.75rem;
}
.bp-hero-inner {
    max-width: 800px; margin: 0 auto;
    padding: 0 2rem;
    display: flex; align-items: flex-start; gap: 16px;
    margin-bottom: 1.75rem;
}
.bp-hero-icon {
    width: 52px; height: 52px; flex-shrink: 0;
    background: rgba(74,222,128,0.12);
    border: 1px solid rgba(74,222,128,0.2);
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    color: #4ade80;
}
.bp-hero-icon svg { width: 26px; height: 26px; }
.bp-hero-inner h1 { font-size: clamp(1.5rem,3vw,2rem); font-weight: 800; color: var(--text); letter-spacing: -0.04em; margin-bottom: 5px; }
.bp-hero-inner p  { font-size: 0.875rem; color: var(--muted); }

/* Steps */
.bp-steps {
    max-width: 800px; margin: 0 auto;
    padding: 0 2rem;
    display: flex; align-items: center; gap: 0;
}
.bstep { display: flex; flex-direction: column; align-items: center; gap: 5px; }
.bstep span { font-size: 0.6rem; color: var(--hint); white-space: nowrap; }
.bstep-dot { width: 28px; height: 28px; border-radius: 50%; background: #1a1a1a; border: 1px solid #333; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 700; color: var(--hint); }
.bstep-dot svg { width: 13px; height: 13px; }
.bstep--done .bstep-dot { background: rgba(74,222,128,0.12); border-color: rgba(74,222,128,0.3); color: #4ade80; }
.bstep--done span { color: #4ade80; }
.bstep--active .bstep-dot { background: var(--gold); border-color: var(--gold); color: #0a0a0a; box-shadow: 0 0 0 4px rgba(200,157,102,0.15); }
.bstep--active span { color: var(--gold); font-weight: 600; }
.bstep-line { flex: 1; height: 1px; background: #222; margin: 0 6px; margin-bottom: 18px; }
.bstep-line--done { background: rgba(74,222,128,0.3); }

/* Content */
.bp-content { max-width: 800px; margin: 0 auto; padding: 2rem; display: flex; flex-direction: column; gap: 1.25rem; }

/* Card */
.bp-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; animation: fadeUp 0.4s ease both; }
@keyframes fadeUp { from{opacity:0;transform:translateY(12px)} to{opacity:1;transform:none} }

/* Card headers */
.bp-card-header { display: flex; align-items: center; gap: 12px; padding: 1.1rem 1.5rem; border-bottom: 1px solid var(--border); }
.bp-card-header h2 { font-size: 1rem; font-weight: 700; color: var(--text); letter-spacing: -0.01em; }
.bp-card-hicon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.bp-card-hicon svg { width: 18px; height: 18px; }
.bp-card-header--blue   { background: rgba(96,165,250,0.04); }
.bp-card-hicon--blue    { background: rgba(96,165,250,0.1); color: #60a5fa; }
.bp-card-header--purple { background: rgba(192,132,252,0.04); }
.bp-card-hicon--purple  { background: rgba(192,132,252,0.1); color: #c084fc; }
.bp-card-header--rose   { background: rgba(244,63,94,0.04); }
.bp-card-hicon--rose    { background: rgba(244,63,94,0.1); color: #f43f5e; }
.bp-card-header--green  { background: rgba(74,222,128,0.04); }
.bp-card-hicon--green   { background: rgba(74,222,128,0.1); color: #4ade80; }
.bp-card-header--gold   { background: rgba(200,157,102,0.06); }
.bp-card-hicon--gold    { background: rgba(200,157,102,0.12); color: var(--gold); }

/* Car body */
.bp-car-body { display: flex; gap: 1.5rem; padding: 1.5rem; align-items: flex-start; flex-wrap: wrap; }
.bp-car-img { width: 240px; height: 160px; border-radius: 10px; overflow: hidden; background: #111; border: 1px solid var(--border); flex-shrink: 0; }
.bp-car-img img { width: 100%; height: 100%; object-fit: cover; }
@media (max-width: 600px) { .bp-car-img { width: 100%; } }
.bp-car-info { flex: 1; min-width: 0; }
.bp-car-info h3 { font-size: 1.4rem; font-weight: 800; color: var(--text); letter-spacing: -0.03em; margin-bottom: 4px; }
.bp-car-meta { font-size: 0.82rem; color: var(--muted); margin-bottom: 1rem; }
.bp-car-specs { display: grid; grid-template-columns: repeat(4,1fr); gap: 8px; margin-bottom: 1.1rem; }
@media (max-width: 500px) { .bp-car-specs { grid-template-columns: repeat(2,1fr); } }
.bp-spec-pill { background: var(--bg-2); border: 1px solid var(--border-2); border-radius: 8px; padding: 8px 10px; text-align: center; }
.bp-spec-pill span { display: block; font-size: 0.6rem; color: var(--hint); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 3px; }
.bp-spec-pill strong { font-size: 0.82rem; color: var(--text); font-weight: 600; }
.bp-daily-rate { display: flex; align-items: baseline; gap: 6px; }
.bp-daily-rate strong { font-size: 1.6rem; font-weight: 800; color: var(--gold); letter-spacing: -0.04em; }
.bp-daily-rate span { font-size: 0.75rem; color: var(--muted); }

/* Dates */
.bp-dates { display: flex; align-items: center; gap: 12px; padding: 1.5rem; flex-wrap: wrap; }
@media (max-width: 600px) { .bp-dates { flex-direction: column; } .bp-date-arrow { transform: rotate(90deg); } }
.bp-date-card { flex: 1; min-width: 160px; border-radius: 10px; padding: 1rem 1.1rem; display: flex; flex-direction: column; gap: 5px; }
.bp-date-card--pickup  { background: rgba(96,165,250,0.06); border: 1px solid rgba(96,165,250,0.15); }
.bp-date-card--return  { background: rgba(74,222,128,0.06); border: 1px solid rgba(74,222,128,0.15); }
.bp-date-label { display: flex; align-items: center; gap: 6px; font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--muted); }
.bp-date-card--pickup .bp-date-label { color: #60a5fa; }
.bp-date-card--return .bp-date-label { color: #4ade80; }
.bp-date-label svg { width: 14px; height: 14px; }
.bp-date-val { font-size: 1.1rem; font-weight: 700; color: var(--text); letter-spacing: -0.02em; }
.bp-date-time { font-size: 0.75rem; color: var(--muted); }
.bp-date-arrow { display: flex; flex-direction: column; align-items: center; gap: 6px; color: var(--hint); flex-shrink: 0; }
.bp-date-arrow svg { width: 22px; height: 22px; }
.bp-days-badge { font-size: 0.68rem; font-weight: 700; color: var(--gold); background: rgba(200,157,102,0.1); border: 1px solid rgba(200,157,102,0.2); padding: 3px 10px; border-radius: 100px; white-space: nowrap; }

/* Locations */
.bp-locs { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; padding: 1.5rem; }
@media (max-width: 620px) { .bp-locs { grid-template-columns: 1fr; } }
.bp-loc { border-radius: 10px; padding: 1.1rem; display: flex; flex-direction: column; gap: 4px; }
.bp-loc--pickup  { background: rgba(96,165,250,0.05); border: 1px solid rgba(96,165,250,0.12); }
.bp-loc--dropoff { background: rgba(74,222,128,0.05); border: 1px solid rgba(74,222,128,0.12); }
.bp-loc-badge { display: inline-flex; align-items: center; gap: 6px; font-size: 0.62rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 6px; }
.bp-loc-badge svg { width: 13px; height: 13px; }
.bp-loc-badge--blue  { color: #60a5fa; }
.bp-loc-badge--green { color: #4ade80; }
.bp-loc h3 { font-size: 1rem; font-weight: 700; color: var(--text); }
.bp-loc p  { font-size: 0.78rem; color: var(--muted); }
.bp-loc span { font-size: 0.72rem; color: var(--hint); }
.bp-loc-detail { display: flex; align-items: center; gap: 5px; font-size: 0.75rem; color: var(--muted); margin-top: 4px; }
.bp-loc-detail svg { width: 13px; height: 13px; }
.bp-loc-detail--green { color: #4ade80; }
.bp-diff-notice { display: flex; align-items: flex-start; gap: 10px; margin: 0 1.5rem 1.5rem; background: rgba(251,146,60,0.08); border: 1px solid rgba(251,146,60,0.2); border-radius: 10px; padding: 12px 14px; }
.bp-diff-notice svg { width: 18px; height: 18px; color: #fb923c; flex-shrink: 0; margin-top: 1px; }
.bp-diff-notice strong { display: block; font-size: 0.82rem; font-weight: 700; color: #fb923c; margin-bottom: 3px; }
.bp-diff-notice p { font-size: 0.78rem; color: var(--muted); }
.bp-diff-notice p span { color: #fb923c; font-weight: 700; }

/* Insurance */
.bp-insurance-row { display: flex; align-items: center; gap: 14px; background: rgba(74,222,128,0.04); border: 1px solid rgba(74,222,128,0.1); border-radius: 10px; padding: 1rem 1.1rem; }
.bp-ins-icon { width: 44px; height: 44px; border-radius: 12px; background: rgba(74,222,128,0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: #4ade80; }
.bp-ins-icon svg { width: 22px; height: 22px; }
.bp-ins-info { flex: 1; }
.bp-ins-info h3 { font-size: 1rem; font-weight: 700; color: var(--text); margin-bottom: 3px; }
.bp-ins-info p  { font-size: 0.75rem; color: var(--muted); }
.bp-ins-price { text-align: right; flex-shrink: 0; }
.bp-ins-price strong { display: block; font-size: 1.4rem; font-weight: 800; color: #4ade80; letter-spacing: -0.03em; }
.bp-ins-price span   { font-size: 0.65rem; color: var(--hint); }
.bp-no-ins { display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 10px; padding: 1rem 1.1rem; font-size: 0.85rem; color: var(--muted); }
.bp-no-ins svg { width: 22px; height: 22px; color: #fbbf24; }


/* Notice Grid */
.bp-notice-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 1.25rem; }
@media (max-width: 580px) { .bp-notice-grid { grid-template-columns: 1fr; } }

.bp-notice-item {
    display: flex; align-items: flex-start; gap: 10px;
    padding: 12px 14px; border-radius: 10px;
}
.bp-notice-icon {
    width: 34px; height: 34px; border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.bp-notice-icon svg { width: 17px; height: 17px; }
.bp-notice-item h4 { font-size: 0.8rem; font-weight: 700; margin-bottom: 3px; }
.bp-notice-item p  { font-size: 0.72rem; color: var(--muted); line-height: 1.5; margin-bottom: 5px; }

.bp-notice-item--purple { background: rgba(192,132,252,0.06); border: 1px solid rgba(192,132,252,0.15); }
.bp-notice-item--purple .bp-notice-icon { background: rgba(192,132,252,0.12); color: #c084fc; }
.bp-notice-item--purple h4 { color: #d8b4fe; }

.bp-notice-item--orange { background: rgba(251,146,60,0.06); border: 1px solid rgba(251,146,60,0.15); }
.bp-notice-item--orange .bp-notice-icon { background: rgba(251,146,60,0.12); color: #fb923c; }
.bp-notice-item--orange h4 { color: #fdba74; }

.bp-notice-item--blue   { background: rgba(96,165,250,0.06); border: 1px solid rgba(96,165,250,0.15); }
.bp-notice-item--blue   .bp-notice-icon { background: rgba(96,165,250,0.12); color: #60a5fa; }
.bp-notice-item--blue   h4 { color: #93c5fd; }

.bp-notice-item--green  { background: rgba(74,222,128,0.06); border: 1px solid rgba(74,222,128,0.15); }
.bp-notice-item--green  .bp-notice-icon { background: rgba(74,222,128,0.12); color: #4ade80; }
.bp-notice-item--green  h4 { color: #86efac; }

.bp-notice-item--red    { background: rgba(248,113,113,0.06); border: 1px solid rgba(248,113,113,0.15); }
.bp-notice-item--red    .bp-notice-icon { background: rgba(248,113,113,0.12); color: #f87171; }
.bp-notice-item--red    h4 { color: #fca5a5; }

.bp-notice-amount { font-size: 0.82rem; font-weight: 700; color: var(--gold); }
.bp-notice-badge { display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; }
.bp-notice-badge--green { background: rgba(74,222,128,0.15); color: #4ade80; }
.bp-notice-badge--red   { background: rgba(248,113,113,0.15); color: #f87171; }

/* Acknowledge */
.bp-acknowledge {
    background: rgba(200,157,102,0.05);
    border: 1px solid rgba(200,157,102,0.2);
    border-radius: 10px;
    padding: 12px 14px;
}
.bp-ack-label {
    display: flex; align-items: flex-start; gap: 10px;
    cursor: pointer;
}
.bp-ack-label input { display: none; }
.bp-ack-box {
    width: 20px; height: 20px; border-radius: 5px;
    border: 1.5px solid #444; flex-shrink: 0; margin-top: 1px;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.2s; background: transparent;
}
.bp-ack-box svg { width: 12px; height: 12px; color: #0a0a0a; opacity: 0; transition: opacity 0.2s; }
.bp-ack-label input:checked ~ .bp-ack-box { background: var(--gold); border-color: var(--gold); }
.bp-ack-label input:checked ~ .bp-ack-box svg { opacity: 1; }
.bp-ack-text { font-size: 0.8rem; color: #ccc; line-height: 1.5; }

/* Summary */
.bp-summary-body { padding: 1.5rem 1.75rem; }
.bp-lines { display: flex; flex-direction: column; gap: 10px; margin-bottom: 1.25rem; }
.bp-line { display: flex; align-items: center; justify-content: space-between; font-size: 0.875rem; }
.bp-line span { color: var(--muted); }
.bp-line strong { color: var(--text); font-weight: 700; }
.bp-line--orange span, .bp-line--orange strong { color: #fb923c; }
.bp-line--green  span, .bp-line--green  strong  { color: #4ade80; }

/* Coupon */
.bp-coupon-section { background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 10px; padding: 1rem 1.1rem; margin-bottom: 1.25rem; }
.bp-coupon-label { display: block; font-size: 0.72rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.07em; margin-bottom: 0.6rem; }
.bp-coupon-row { display: flex; gap: 8px; }
.bp-coupon-input-wrap { flex: 1; position: relative; }
.bp-coupon-input-wrap svg { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); width: 15px; height: 15px; color: var(--hint); pointer-events: none; }
.bp-coupon-input-wrap input {
    width: 100% !important; background: var(--bg-2) !important; border: 1px solid var(--border-2) !important;
    border-radius: 8px !important; color: var(--text) !important; font-size: 0.85rem !important;
    padding: 0.65rem 0.75rem 0.65rem 2.2rem !important; outline: none !important;
    -webkit-text-fill-color: var(--text) !important; font-family: monospace; letter-spacing: 0.05em;
    transition: border-color var(--tr);
}
.bp-coupon-input-wrap input:-webkit-autofill { -webkit-box-shadow: 0 0 0 1000px var(--bg-2) inset !important; -webkit-text-fill-color: var(--text) !important; }
.bp-coupon-input-wrap input::placeholder { color: var(--hint) !important; font-family: monospace; }
.bp-coupon-input-wrap input:focus { border-color: var(--gold) !important; }
.bp-coupon-btn { padding: 0 1.1rem; border-radius: 8px; font-size: 0.82rem; font-weight: 700; cursor: pointer; border: none; transition: opacity var(--tr); white-space: nowrap; }
.bp-coupon-btn--apply  { background: var(--gold); color: #fff; }
.bp-coupon-btn--remove { background: rgba(248,113,113,0.15); border: 1px solid rgba(248,113,113,0.3); color: #f87171; }
.bp-coupon-btn:hover { opacity: 0.85; }
.bp-coupon-error { display: flex; align-items: center; gap: 6px; font-size: 0.75rem; color: #f87171; margin-top: 6px; }
.bp-coupon-error svg { width: 14px; height: 14px; }
.bp-coupon-success { display: flex; align-items: flex-start; gap: 8px; background: rgba(74,222,128,0.08); border: 1px solid rgba(74,222,128,0.2); border-radius: 8px; padding: 10px 12px; margin-top: 8px; color: #4ade80; }
.bp-coupon-success svg { width: 16px; height: 16px; flex-shrink: 0; margin-top: 2px; }
.bp-coupon-success strong { display: block; font-size: 0.78rem; font-weight: 700; margin-bottom: 2px; }
.bp-coupon-success p { font-size: 0.72rem; color: #9ca3af; }

/* Grand total */
.bp-grand-total { position: relative; background: linear-gradient(135deg, #1a1408, #201a0a); border: 1px solid rgba(200,157,102,0.25); border-radius: 12px; overflow: hidden; margin-bottom: 1.5rem; }
.bp-grand-glow { position: absolute; top: -40px; right: -40px; width: 120px; height: 120px; background: radial-gradient(circle, rgba(200,157,102,0.2) 0%, transparent 70%); pointer-events: none; }
.bp-grand-inner { display: flex; align-items: center; justify-content: space-between; padding: 1.25rem 1.4rem; position: relative; z-index: 1; }
.bp-grand-inner span { font-size: 1rem; font-weight: 700; color: rgba(200,157,102,0.8); }
.bp-grand-inner strong { font-size: 2rem; font-weight: 800; color: var(--gold); letter-spacing: -0.04em; }
.bp-saved { font-size: 0.75rem; color: #4ade80; text-align: center; padding-bottom: 0.75rem; position: relative; z-index: 1; }

/* Actions */
.bp-confirm-btn { display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; padding: 1rem; background: linear-gradient(135deg, #4ade80, #22c55e); border: none; border-radius: 10px; color: #0a0a0a; font-size: 1rem; font-weight: 800; cursor: pointer; transition: opacity var(--tr), box-shadow var(--tr), transform 0.15s; box-shadow: 0 4px 20px rgba(74,222,128,0.2); margin-bottom: 0.75rem; }
.bp-confirm-btn svg { width: 20px; height: 20px; }
.bp-confirm-btn:hover { opacity: 0.9; box-shadow: 0 6px 28px rgba(74,222,128,0.35); }
.bp-confirm-btn:active { transform: scale(0.985); }
.bp-back-link { display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.82rem; color: var(--muted); text-decoration: none; padding: 0.75rem; border-radius: 10px; border: 1px solid var(--border); transition: all var(--tr); }
.bp-back-link svg { width: 15px; height: 15px; }
.bp-back-link:hover { border-color: var(--border-2); color: var(--text); }
.bp-secure { display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.7rem; color: var(--hint); margin-top: 1rem; }
.bp-secure svg { width: 12px; height: 12px; color: #4ade80; }

/* RTL */
[dir="rtl"] .bp-coupon-input-wrap svg { left: auto; right: 11px; }
[dir="rtl"] .bp-coupon-input-wrap input { padding: 0.65rem 2.2rem 0.65rem 0.75rem !important; }
[dir="rtl"] .bp-back-link svg { transform: scaleX(-1); }
[dir="rtl"] .bp-date-arrow svg { transform: scaleX(-1); }
[dir="rtl"] .bp-hero-inner { flex-direction: row-reverse; }
[dir="rtl"] .bp-grand-glow { right: auto; left: -40px; }
</style>

@endsection