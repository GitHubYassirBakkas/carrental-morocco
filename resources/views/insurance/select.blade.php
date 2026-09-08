@extends('layouts.app')

@section('content')
<div class="ins-page" x-data="insurancePage()">

    {{-- ══════════════════════════════
         HERO BAR
    ══════════════════════════════ --}}
    <div class="ins-hero">
        <div class="ins-hero-overlay"></div>
        <div class="ins-hero-inner">
            {{-- Breadcrumb --}}
            <div class="ins-breadcrumb">
                <a href="{{ route('cars.index') }}">{{ __('messages.cars') }}</a>
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                <span>{{ $car->brand }} {{ $car->model }}</span>
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                <span class="bc-active">{{ __('messages.select_insurance') }}</span>
            </div>

            <div class="ins-hero-badge">
                <span></span>{{ __('messages.insurance') }}
            </div>
            <h1 class="ins-hero-title">{{ __('messages.select_insurance') }}</h1>
            <p class="ins-hero-sub">{{ __('messages.insurance_subtitle') }}</p>

            {{-- Steps --}}
            <div class="ins-steps">
                <div class="istep istep--done">
                    <div class="istep-dot"><svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg></div>
                    <span>{{ __('messages.step_2_title') }}</span>
                </div>
                <div class="istep-line"></div>
                <div class="istep istep--active">
                    <div class="istep-dot">2</div>
                    <span>{{ __('messages.insurance') }}</span>
                </div>
                <div class="istep-line"></div>
                <div class="istep">
                    <div class="istep-dot">3</div>
                    <span>{{ __('messages.booking_details') }}</span>
                </div>
                <div class="istep-line"></div>
                <div class="istep">
                    <div class="istep-dot">4</div>
                    <span>{{ __('messages.payment') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════
         CONTENT
    ══════════════════════════════ --}}
    <div class="ins-content">

        {{-- ─────── LEFT: Insurance plans ─────── --}}
        <div class="ins-plans">

            <div class="plans-header">
                <div class="plans-header-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div>
                    <h2>{{ __('messages.select_insurance') }}</h2>
                    <p>{{ __('messages.insurance_choose_desc') }}</p>
                </div>
            </div>

            {{-- ─── Important Notice ─── --}}
<div class="ins-notice-box">
    <div class="ins-notice-header">
        <svg viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        {{ __('messages.ins_not_covered_title') }}
    </div>
    <div class="ins-notice-items">
        <div class="ins-notice-item ins-notice-item--red">
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            <div>
                <strong>{{ __('messages.late_return_title') }}</strong>
                <p>{{ __('messages.late_return_desc', ['grace' => \App\Models\Booking::GRACE_MINUTES / 60, 'fee' => \App\Models\Booking::HOURLY_LATE_FEE]) }}</p>
            </div>
        </div>
        <div class="ins-notice-item ins-notice-item--orange">
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            <div>
                <strong>{{ __('messages.cov_fuel') }}</strong>
                <p>{{ __('messages.fuel_not_covered_desc') }}</p>
            </div>
        </div>
        <div class="ins-notice-item ins-notice-item--blue">
            <svg viewBox="0 0 20 20" fill="currentColor"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4zM18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z"/></svg>
            <div>
                <strong>{{ __('messages.deposit_title') }}</strong>
                <p>{{ __('messages.deposit_ins_desc') }}</p>
            </div>
        </div>
    </div>
</div>

            <form id="insuranceForm" action="{{ route('insurance.store', $car) }}" method="POST" class="plans-list">
                @csrf

                @foreach($insurances as $insurance)
                    @php
                        $tier = match(true) {
                            str_contains(strtolower($insurance->name), 'basic')    => ['label' => __('messages.ins_essential'),   'cls' => 'tier-basic'],
                            str_contains(strtolower($insurance->name), 'standard') => ['label' => __('messages.ins_recommended'), 'cls' => 'tier-standard'],
                            str_contains(strtolower($insurance->name), 'premium')  => ['label' => __('messages.ins_complete'),    'cls' => 'tier-premium'],
                            default                                                 => ['label' => __('messages.insurance'),       'cls' => ''],
                        };
                    @endphp

                    <label class="plan-card"
                           :class="selectedInsurance == {{ $insurance->id }} ? 'plan-card--active' : ''">

                        <input type="radio" name="insurance_id" value="{{ $insurance->id }}"
                               class="hidden" x-model="selectedInsurance">

                        {{-- Checkmark --}}
                        <div class="plan-check"
                             :class="selectedInsurance == {{ $insurance->id }} ? 'plan-check--on' : ''">
                            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        </div>

                        <div class="plan-body">
                            {{-- Title row --}}
                            <div class="plan-title-row">
                                <h3 class="plan-name">{{ $insurance->name }}</h3>
                                <span class="plan-tier {{ $tier['cls'] }}">{{ $tier['label'] }}</span>
                            </div>

                            {{-- Description --}}
                            <p class="plan-desc">{{ $insurance->description }}</p>

                            {{-- Features --}}
                            @if($insurance->features && count($insurance->features) > 0)
                                <div class="plan-features">
                                    @foreach($insurance->features as $feature)
                                        <span class="plan-feat">
                                            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                            {{ $feature }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Price --}}
                            <div class="plan-price-row">
                                <div class="plan-price-wrap">
                                    <strong class="plan-price">{{ number_format($insurance->daily_rate, 0) }}</strong>
                                    <span class="plan-price-curr">MAD</span>
                                    <span class="plan-price-label">{{ __('messages.insurance_one_time') }}</span>
                                </div>
                            </div>
                        </div>
                    </label>
                @endforeach
            </form>
        </div>

        {{-- ─────── RIGHT: Summary ─────── --}}
        <aside class="ins-summary">
            <div class="summary-card">

                {{-- Car --}}
                <div class="sum-section">
                    <p class="sum-label">{{ __('messages.car_details') }}</p>

                    @if(!empty($car->image))
                        <div class="sum-car-img">
                            <img src="{{ asset('storage/cars/'.$car->image) }}"
                                 alt="{{ $car->brand }} {{ $car->model }}">
                        </div>
                    @endif

                    <h3 class="sum-car-name">{{ $car->brand }} {{ $car->model }}</h3>

                    @if($car->location)
                        <p class="sum-car-loc">
                            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                            {{ $car->location->name }}
                        </p>
                    @endif

                    <div class="sum-specs">
                        <span>
                            <svg viewBox="0 0 20 20" fill="currentColor"><path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/></svg>
                            {{ $car->seats }} {{ __('messages.seats') }}
                        </span>
                        <span>
                            <svg viewBox="0 0 20 20" fill="currentColor"><path d="M8 2a2 2 0 00-2 2v1H5a3 3 0 00-3 3v9a2 2 0 002 2h12a2 2 0 002-2V8a3 3 0 00-3-3h-1V4a2 2 0 00-2-2H8zm0 2h4v1H8V4z"/></svg>
                            {{ $car->luggage }} {{ __('messages.doors') }}
                        </span>
                        <span>
                            <svg viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a1 1 0 011 1v1.323l3.954 1.582 1.599-.8a1 1 0 01.894 1.79l-1.233.616 1.738 5.42a1 1 0 01-.285 1.05A3.989 3.989 0 0115 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.715-5.349L11 6.477V16h2a1 1 0 110 2H7a1 1 0 110-2h2V6.477L6.237 7.582l1.715 5.349a1 1 0 01-.285 1.05A3.989 3.989 0 015 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.738-5.42-1.233-.617a1 1 0 01.894-1.788l1.599.799L9 4.323V3a1 1 0 011-1z"/></svg>
                            {{ $car->transmission }}
                        </span>
                    </div>
                </div>

                <div class="sum-divider"></div>

                {{-- Price breakdown --}}
                <div class="sum-section">
                    <p class="sum-label">{{ __('messages.price_range') }}</p>

                    <div class="sum-rows">
                        <div class="sum-row">
                            <span>
                                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/><path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/></svg>
                                {{ __('messages.price_per_day') }}
                            </span>
                            <strong>{{ number_format($car->price_per_day, 0) }} MAD</strong>
                        </div>
                        <div class="sum-row">
                            <span>
                                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                {{ __('messages.insurance') }}
                            </span>
                            <strong x-text="insurancePrice + ' MAD'"></strong>
                        </div>
                    </div>
                </div>

                {{-- Total --}}
                <div class="sum-total">
                    <div class="sum-total-glow"></div>
                    <div class="sum-total-inner">
                        <p class="sum-total-label">{{ __('messages.total_price') }}</p>
                        <p class="sum-total-num" x-text="totalPrice + ' MAD'"></p>
                        <p class="sum-total-note">{{ __('messages.insurance_one_time') }}</p>
                    </div>
                </div>

                {{-- CTA --}}
                <button type="submit" form="insuranceForm" class="sum-cta">
                    <span>{{ __('messages.proceed_to_payment') }}</span>
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>

                {{-- Security --}}
                <div class="sum-secure">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                    {{ __('messages.secure_payment') }}
                </div>

            </div>
        </aside>

    </div>
</div>

{{-- ══════════════════════════════════════════════
     STYLES
══════════════════════════════════════════════ --}}
<style>
:root {
    --gold:      #C89D66;
    --gold-dark: #B8935E;
    --gold-glow: rgba(200,157,102,0.15);
    --bg:        #0a0a0a;
    --bg-card:   #0f0f0f;
    --bg-2:      #111;
    --bg-input:  #141414;
    --border:    #1e1e1e;
    --border-2:  #2a2a2a;
    --text:      #f0f0f0;
    --muted:     #777;
    --hint:      #444;
    --radius:    14px;
    --tr:        0.22s ease;
}

/* ── Hero ── */
.ins-hero {
    position: relative;
    background: linear-gradient(135deg, #0d0d0d 0%, #111008 100%);
    border-bottom: 1px solid var(--border);
    overflow: hidden;
    padding: 2.5rem 0 2rem;
}

.ins-hero-overlay {
    position: absolute; inset: 0;
    background:
        radial-gradient(ellipse at 10% 50%, rgba(200,157,102,0.06) 0%, transparent 60%),
        radial-gradient(ellipse at 90% 20%, rgba(200,157,102,0.04) 0%, transparent 50%);
    pointer-events: none;
}

.ins-hero-inner {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
    position: relative;
    z-index: 1;
}

/* Breadcrumb */
.ins-breadcrumb {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.72rem;
    color: var(--hint);
    margin-bottom: 1.25rem;
}

.ins-breadcrumb a { color: var(--muted); text-decoration: none; transition: color var(--tr); }
.ins-breadcrumb a:hover { color: var(--gold); }
.ins-breadcrumb svg { width: 12px; height: 12px; }
.bc-active { color: var(--gold); font-weight: 600; }

/* Badge */
.ins-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(200,157,102,0.1);
    border: 1px solid rgba(200,157,102,0.2);
    border-radius: 100px;
    padding: 4px 14px;
    font-size: 0.7rem;
    font-weight: 700;
    color: var(--gold);
    letter-spacing: 0.1em;
    text-transform: uppercase;
    margin-bottom: 1rem;
}

.ins-hero-badge span {
    width: 6px; height: 6px;
    border-radius: 50%;
    background: var(--gold);
    display: inline-block;
    animation: pulse 2s ease infinite;
}

@keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:0.4;transform:scale(0.8)} }

.ins-hero-title {
    font-size: clamp(1.6rem, 3.5vw, 2.5rem);
    font-weight: 800;
    color: var(--text);
    letter-spacing: -0.04em;
    margin-bottom: 0.4rem;
}

.ins-hero-sub {
    font-size: 0.9rem;
    color: var(--muted);
    margin-bottom: 1.75rem;
}

/* Steps */
.ins-steps {
    display: flex;
    align-items: center;
    gap: 0;
}

.istep {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
}

.istep span {
    font-size: 0.6rem;
    color: var(--hint);
    white-space: nowrap;
    letter-spacing: 0.04em;
}

.istep-dot {
    width: 30px; height: 30px;
    border-radius: 50%;
    background: #1a1a1a;
    border: 1px solid #333;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--hint);
}

.istep-dot svg { width: 14px; height: 14px; }

.istep--done .istep-dot {
    background: rgba(200,157,102,0.12);
    border-color: rgba(200,157,102,0.3);
    color: var(--gold);
}

.istep--done span { color: var(--gold); }

.istep--active .istep-dot {
    background: var(--gold);
    border-color: var(--gold);
    color: #0a0a0a;
    box-shadow: 0 0 0 4px rgba(200,157,102,0.15);
}

.istep--active span { color: var(--gold); font-weight: 600; }

.istep-line {
    width: 50px; height: 1px;
    background: #222;
    margin: 0 6px;
    margin-bottom: 18px;
    flex-shrink: 0;
}

/* ── Layout ── */
.ins-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 1.75rem;
    align-items: start;
}

@media (max-width: 1024px) {
    .ins-content { grid-template-columns: 1fr; }
    .ins-summary { order: -1; }
}

/* ── Plans ── */
.ins-plans {}

.plans-header {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    margin-bottom: 1.5rem;
    padding-bottom: 1.25rem;
    border-bottom: 1px solid var(--border);
}

.plans-header-icon {
    width: 44px; height: 44px;
    border-radius: 12px;
    background: rgba(200,157,102,0.1);
    border: 1px solid rgba(200,157,102,0.2);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    color: var(--gold);
}

.plans-header-icon svg { width: 22px; height: 22px; }

.plans-header h2 {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text);
    letter-spacing: -0.02em;
    margin-bottom: 3px;
}

.plans-header p {
    font-size: 0.825rem;
    color: var(--muted);
}


/* Insurance Notice Box */
.ins-notice-box {
    margin-top: 1.5rem;
    background: rgba(255,255,255,0.02);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
}
.ins-notice-header {
    display: flex; align-items: center; gap: 8px;
    padding: 12px 16px;
    background: rgba(251,191,36,0.06);
    border-bottom: 1px solid rgba(251,191,36,0.1);
    font-size: 0.82rem; font-weight: 700;
    color: #fbbf24;
}
.ins-notice-header svg { width: 16px; height: 16px; flex-shrink: 0; }
.ins-notice-items { padding: 12px 16px; display: flex; flex-direction: column; gap: 10px; }
.ins-notice-item {
    display: flex; align-items: flex-start; gap: 10px;
    padding: 10px 12px; border-radius: 8px;
}
.ins-notice-item svg { width: 16px; height: 16px; flex-shrink: 0; margin-top: 2px; }
.ins-notice-item strong { display: block; font-size: 0.8rem; font-weight: 700; margin-bottom: 2px; }
.ins-notice-item p { font-size: 0.72rem; color: var(--muted); line-height: 1.5; }
.ins-notice-item--red    { background: rgba(248,113,113,0.06); border: 1px solid rgba(248,113,113,0.15); }
.ins-notice-item--red    svg { color: #f87171; }
.ins-notice-item--red    strong { color: #fca5a5; }
.ins-notice-item--orange { background: rgba(251,146,60,0.06); border: 1px solid rgba(251,146,60,0.15); }
.ins-notice-item--orange svg { color: #fb923c; }
.ins-notice-item--orange strong { color: #fdba74; }
.ins-notice-item--blue   { background: rgba(96,165,250,0.06); border: 1px solid rgba(96,165,250,0.15); }
.ins-notice-item--blue   svg { color: #60a5fa; }
.ins-notice-item--blue   strong { color: #93c5fd; }

.plans-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

/* Plan Card */
.plan-card {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.4rem;
    cursor: pointer;
    transition: border-color var(--tr), background var(--tr), box-shadow var(--tr), transform var(--tr);
    position: relative;
    animation: fadeUp 0.4s ease both;
}

.plan-card:hover {
    border-color: var(--border-2);
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.3);
}

.plan-card--active {
    border-color: rgba(200,157,102,0.5) !important;
    background: rgba(200,157,102,0.04) !important;
    box-shadow: 0 0 0 1px rgba(200,157,102,0.2), 0 12px 36px rgba(0,0,0,0.3) !important;
}

@keyframes fadeUp { from{opacity:0;transform:translateY(12px)} to{opacity:1;transform:none} }

/* Checkmark */
.plan-check {
    width: 26px; height: 26px;
    border-radius: 50%;
    border: 1px solid var(--border-2);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    transition: all var(--tr);
    margin-top: 3px;
}

.plan-check svg { width: 14px; height: 14px; color: #0a0a0a; opacity: 0; transition: opacity var(--tr); }

.plan-check--on {
    background: var(--gold);
    border-color: var(--gold);
    box-shadow: 0 0 0 3px rgba(200,157,102,0.2);
}

.plan-check--on svg { opacity: 1; }

/* Plan body */
.plan-body { flex: 1; min-width: 0; }

.plan-title-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 0.5rem;
    flex-wrap: wrap;
}

.plan-name {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text);
    letter-spacing: -0.02em;
}

.plan-tier {
    padding: 3px 10px;
    border-radius: 100px;
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.tier-basic    { background: rgba(100,160,255,0.1); border: 1px solid rgba(100,160,255,0.2); color: #7fb3ff; }
.tier-standard { background: rgba(200,157,102,0.12); border: 1px solid rgba(200,157,102,0.3); color: var(--gold); }
.tier-premium  { background: rgba(168,85,247,0.1); border: 1px solid rgba(168,85,247,0.2); color: #c084fc; }

.plan-desc {
    font-size: 0.82rem;
    color: var(--muted);
    line-height: 1.6;
    margin-bottom: 0.85rem;
}

/* Features */
.plan-features {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 1rem;
}

.plan-feat {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.72rem;
    color: #bbb;
    background: rgba(255,255,255,0.04);
    border: 1px solid #222;
    padding: 4px 10px;
    border-radius: 6px;
}

.plan-feat svg { width: 11px; height: 11px; color: #4ade80; flex-shrink: 0; }

/* Price */
.plan-price-row {
    border-top: 1px solid var(--border);
    padding-top: 0.85rem;
    display: flex;
    align-items: baseline;
    gap: 6px;
}

.plan-price-wrap {
    display: flex;
    align-items: baseline;
    gap: 5px;
}

.plan-price {
    font-size: 2rem;
    font-weight: 800;
    color: var(--gold);
    letter-spacing: -0.04em;
}

.plan-price-curr {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--muted);
}

.plan-price-label {
    font-size: 0.72rem;
    color: var(--hint);
}

/* ── Summary ── */
.ins-summary {}

.summary-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    position: sticky;
    top: 90px;
}

.sum-section { padding: 1.25rem 1.4rem; }

.sum-label {
    font-size: 0.65rem;
    font-weight: 700;
    color: var(--hint);
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin-bottom: 0.9rem;
}

.sum-divider { height: 1px; background: var(--border); }

/* Car */
.sum-car-img {
    height: 150px;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 0.85rem;
    background: #111;
    border: 1px solid var(--border);
}

.sum-car-img img { width: 100%; height: 100%; object-fit: cover; }

.sum-car-name {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 5px;
    letter-spacing: -0.02em;
}

.sum-car-loc {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.75rem;
    color: var(--muted);
    margin-bottom: 0.85rem;
}

.sum-car-loc svg { width: 12px; height: 12px; color: #f43f5e; }

.sum-specs {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.sum-specs span {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.72rem;
    color: var(--muted);
    background: var(--bg-2);
    border: 1px solid var(--border);
    padding: 4px 10px;
    border-radius: 6px;
}

.sum-specs svg { width: 13px; height: 13px; color: var(--gold); }

/* Rows */
.sum-rows { display: flex; flex-direction: column; gap: 10px; }

.sum-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.82rem;
}

.sum-row span {
    display: flex;
    align-items: center;
    gap: 7px;
    color: var(--muted);
}

.sum-row svg { width: 14px; height: 14px; color: var(--gold); }
.sum-row strong { color: var(--text); font-weight: 700; }

/* Total */
.sum-total {
    position: relative;
    margin: 0 1.4rem 1.25rem;
    border-radius: 12px;
    overflow: hidden;
    background: linear-gradient(135deg, #1a1408 0%, #201a0a 100%);
    border: 1px solid rgba(200,157,102,0.25);
}

.sum-total-glow {
    position: absolute;
    top: -30px; right: -30px;
    width: 100px; height: 100px;
    background: radial-gradient(circle, rgba(200,157,102,0.2) 0%, transparent 70%);
    pointer-events: none;
}

.sum-total-inner { padding: 1.1rem 1.25rem; position: relative; z-index: 1; }

.sum-total-label {
    font-size: 0.68rem;
    font-weight: 700;
    color: rgba(200,157,102,0.7);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 4px;
}

.sum-total-num {
    font-size: 2.2rem;
    font-weight: 800;
    color: var(--gold);
    letter-spacing: -0.04em;
    line-height: 1;
    margin-bottom: 4px;
}

.sum-total-note {
    font-size: 0.68rem;
    color: var(--hint);
}

/* CTA */
.sum-cta {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: calc(100% - 2.8rem);
    margin: 0 1.4rem 0.85rem;
    padding: 0.95rem;
    background: linear-gradient(135deg, var(--gold), var(--gold-dark));
    border: none;
    border-radius: 10px;
    color: #fff;
    font-size: 0.9rem;
    font-weight: 700;
    cursor: pointer;
    transition: opacity var(--tr), box-shadow var(--tr), transform 0.15s;
    box-shadow: 0 4px 20px rgba(200,157,102,0.25);
}

.sum-cta svg { width: 16px; height: 16px; transition: transform 0.2s; }
.sum-cta:hover { opacity: 0.9; box-shadow: 0 6px 28px rgba(200,157,102,0.4); }
.sum-cta:hover svg { transform: translateX(3px); }
.sum-cta:active { transform: scale(0.985); }

/* Secure */
.sum-secure {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    font-size: 0.72rem;
    color: var(--hint);
    padding: 0 1.4rem 1.25rem;
}

.sum-secure svg { width: 13px; height: 13px; color: #4ade80; }

/* RTL */
[dir="rtl"] .ins-breadcrumb svg { transform: scaleX(-1); }
[dir="rtl"] .plan-card { flex-direction: row-reverse; }
[dir="rtl"] .plan-check { margin-top: 3px; }
[dir="rtl"] .sum-cta svg { transform: scaleX(-1); }
[dir="rtl"] .sum-cta:hover svg { transform: scaleX(-1) translateX(-3px); }
</style>

@push('scripts')
<script>
function insurancePage() {
    return {
        selectedInsurance: {{ $insurances->first()->id ?? 0 }},
        carPrice: Number({{ $car->price_per_day }}),
        insurances: @json($insurances->keyBy('id')),

        get insurancePrice() {
            return Number(this.insurances[this.selectedInsurance]?.daily_rate ?? 0).toFixed(0);
        },

        get totalPrice() {
            return (Number(this.carPrice) + Number(this.insurancePrice)).toFixed(0);
        }
    }
}
</script>
@endpush

@endsection