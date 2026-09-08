@extends('layouts.app')

@section('content')

<div class="success-page">

    {{-- Background particles --}}
    <div class="success-bg">
        <div class="sb-orb sb-orb--1"></div>
        <div class="sb-orb sb-orb--2"></div>
    </div>

    <div class="success-wrap">

        {{-- Animated checkmark --}}
        <div class="success-icon">
            <svg class="success-check" viewBox="0 0 52 52">
                <circle class="check-circle" cx="26" cy="26" r="24" fill="none" stroke-width="2"/>
                <path class="check-tick" fill="none" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" d="M14 27l8 8 16-16"/>
            </svg>
        </div>

        <h1 class="success-title">{{ __('bookingconfirmed') }} 🎉</h1>
        <p class="success-sub">{{ __('bookingconfirmed') }}</p>

        {{-- Reference badge --}}
        @if($booking->reference ?? $booking->id)
        <div class="success-ref">
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/></svg>
            {{ __('messages.booking_reference') }}: <strong>#{{ $booking->reference ?? str_pad($booking->id, 6, '0', STR_PAD_LEFT) }}</strong>
        </div>
        @endif

        {{-- Booking details card --}}
        <div class="success-card">

            {{-- Car --}}
            <div class="sc-row sc-row--car">
                <div class="sc-icon sc-icon--blue">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/><path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/></svg>
                </div>
                <div class="sc-info">
                    <span>{{ __('messages.car_details') }}</span>
                    <strong>{{ $booking->car->brand }} {{ $booking->car->model }}</strong>
                </div>
            </div>

            <div class="sc-divider"></div>

            {{-- Dates --}}
            <div class="sc-dates">
                <div class="sc-date">
                    <div class="sc-icon sc-icon--purple">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                    </div>
                    <div class="sc-info">
                        <span>{{ __('messages.pickup_date') }}</span>
                        <strong>{{ \Carbon\Carbon::parse($booking->start_date)->format('d M Y') }}</strong>
                    </div>
                </div>
                <div class="sc-date-arrow">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </div>
                <div class="sc-date">
                    <div class="sc-icon sc-icon--green">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                    </div>
                    <div class="sc-info">
                        <span>{{ __('messages.return_date') }}</span>
                        <strong>{{ \Carbon\Carbon::parse($booking->end_date)->format('d M Y') }}</strong>
                    </div>
                </div>
            </div>

            <div class="sc-divider"></div>

            {{-- Total & status --}}
            <div class="sc-bottom">
                <div class="sc-total">
                    <span>{{ __('messages.total_price') }}</span>
                    <strong>{{ number_format($booking->total_amount, 0) }} MAD</strong>
                </div>
                <div class="sc-status">
                    <span>{{ __('messages.booking_status') }}</span>
                    <div class="sc-status-badge">
                        <span class="sc-status-dot"></span>
                        {{ ucfirst($booking->status) }}
                    </div>
                </div>
            </div>

        </div>

        {{-- Actions --}}
        <div class="success-actions">
            <a href="{{ route('my_booking.index') }}" class="sa-btn sa-btn--primary">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>
                {{ __('messages.my_bookings') }}
            </a>
            <a href="{{ route('cars.index') }}" class="sa-btn sa-btn--outline">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/><path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/></svg>
                {{ __('rent_another car') }}
            </a>
            <a href="{{ route('home') }}" class="sa-link">{{ __('messages.back_to_home') }}</a>
        </div>

    </div>
</div>

<style>
:root {
    --gold:   #C89D66;
    --gold-d: #B8935E;
    --bg:     #0a0a0a;
    --card:   #0f0f0f;
    --b1:     #1e1e1e;
    --b2:     #2a2a2a;
    --text:   #f0f0f0;
    --muted:  #777;
    --hint:   #444;
    --tr:     0.2s ease;
}

.success-page {
    min-height: 100vh;
    background: var(--bg);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 1rem;
    position: relative;
    overflow: hidden;
}

/* Background orbs */
.success-bg { position: absolute; inset: 0; pointer-events: none; }
.sb-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.06;
}
.sb-orb--1 { width: 400px; height: 400px; background: #4ade80; top: -100px; left: -100px; }
.sb-orb--2 { width: 350px; height: 350px; background: var(--gold); bottom: -80px; right: -80px; }

.success-wrap {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 520px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1.25rem;
    animation: fadeUp 0.5s ease both;
}

@keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:none} }

/* Animated checkmark */
.success-icon { margin-bottom: 0.25rem; }

.success-check { width: 80px; height: 80px; }

.check-circle {
    stroke: #4ade80;
    stroke-dasharray: 166;
    stroke-dashoffset: 166;
    animation: circle-anim 0.6s ease forwards;
}

.check-tick {
    stroke: #4ade80;
    stroke-dasharray: 48;
    stroke-dashoffset: 48;
    animation: tick-anim 0.4s 0.5s ease forwards;
}

@keyframes circle-anim { to { stroke-dashoffset: 0; } }
@keyframes tick-anim   { to { stroke-dashoffset: 0; } }

.success-title {
    font-size: clamp(1.5rem, 4vw, 2rem);
    font-weight: 800;
    color: var(--text);
    letter-spacing: -0.04em;
    text-align: center;
    margin: 0;
}

.success-sub {
    font-size: 0.9rem;
    color: var(--muted);
    text-align: center;
    margin: 0;
}

/* Reference badge */
.success-ref {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: rgba(200,157,102,0.1);
    border: 1px solid rgba(200,157,102,0.25);
    border-radius: 100px;
    padding: 6px 16px;
    font-size: 0.78rem;
    color: var(--muted);
}
.success-ref svg { width: 14px; height: 14px; color: var(--gold); }
.success-ref strong { color: var(--gold); font-weight: 700; }

/* Card */
.success-card {
    width: 100%;
    background: var(--card);
    border: 1px solid var(--b1);
    border-radius: 16px;
    overflow: hidden;
}

.sc-row--car {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 1.1rem 1.4rem;
}

.sc-icon {
    width: 38px; height: 38px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.sc-icon svg { width: 18px; height: 18px; }
.sc-icon--blue   { background: rgba(96,165,250,0.1);  color: #60a5fa; }
.sc-icon--purple { background: rgba(192,132,252,0.1); color: #c084fc; }
.sc-icon--green  { background: rgba(74,222,128,0.1);  color: #4ade80; }

.sc-info { display: flex; flex-direction: column; gap: 2px; }
.sc-info span   { font-size: 0.65rem; color: var(--hint); text-transform: uppercase; letter-spacing: 0.07em; }
.sc-info strong { font-size: 0.95rem; color: var(--text); font-weight: 700; }

.sc-divider { height: 1px; background: var(--b1); }

/* Dates */
.sc-dates {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 1.1rem 1.4rem;
}

.sc-date { flex: 1; display: flex; align-items: center; gap: 10px; }

.sc-date-arrow {
    flex-shrink: 0;
    color: var(--hint);
}
.sc-date-arrow svg { width: 18px; height: 18px; }

/* Bottom row */
.sc-bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.1rem 1.4rem;
    gap: 1rem;
    flex-wrap: wrap;
}

.sc-total { display: flex; flex-direction: column; gap: 3px; }
.sc-total span   { font-size: 0.65rem; color: var(--hint); text-transform: uppercase; letter-spacing: 0.07em; }
.sc-total strong { font-size: 1.5rem; font-weight: 800; color: var(--gold); letter-spacing: -0.03em; }

.sc-status { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; }
.sc-status span  { font-size: 0.65rem; color: var(--hint); text-transform: uppercase; letter-spacing: 0.07em; }
.sc-status-badge {
    display: flex; align-items: center; gap: 6px;
    background: rgba(74,222,128,0.1);
    border: 1px solid rgba(74,222,128,0.2);
    border-radius: 100px;
    padding: 4px 12px;
    font-size: 0.75rem;
    font-weight: 700;
    color: #4ade80;
    text-transform: capitalize;
}
.sc-status-dot {
    width: 6px; height: 6px;
    border-radius: 50%;
    background: #4ade80;
    animation: pulse 2s ease infinite;
}
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.4} }

/* Actions */
.success-actions {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.sa-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 0.85rem;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 700;
    text-decoration: none;
    transition: opacity var(--tr), box-shadow var(--tr), transform 0.15s;
    cursor: pointer;
}

.sa-btn svg { width: 17px; height: 17px; }

.sa-btn--primary {
    background: linear-gradient(135deg, var(--gold), var(--gold-d));
    color: #fff;
    box-shadow: 0 4px 20px rgba(200,157,102,0.22);
}
.sa-btn--primary:hover { opacity: 0.9; box-shadow: 0 6px 28px rgba(200,157,102,0.38); }
.sa-btn--primary:active { transform: scale(0.985); }

.sa-btn--outline {
    background: transparent;
    border: 1px solid var(--b2);
    color: var(--text);
}
.sa-btn--outline:hover { border-color: var(--gold); color: var(--gold); }

.sa-link {
    text-align: center;
    font-size: 0.82rem;
    color: var(--hint);
    text-decoration: none;
    padding: 0.5rem;
    transition: color var(--tr);
}
.sa-link:hover { color: var(--muted); }

/* RTL */
[dir="rtl"] .sc-date-arrow svg { transform: scaleX(-1); }
[dir="rtl"] .sc-status { align-items: flex-start; }
</style>

@endsection