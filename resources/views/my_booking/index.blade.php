@extends('layouts.app')

@section('title', __('messages.my_bookings'))

@section('content')

<div class="mb-page">

    {{-- ══ HERO ══ --}}
    <div class="mb-hero">
        <div class="mb-hero-inner">
            <div>
                <div class="mb-eyebrow">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>
                    {{ __('messages.my_bookings') }}
                </div>
                <h1>{{ __('messages.booking_history_title') }}</h1>
                <p>{{ __('messages.booking_history_desc') }}</p>
            </div>

            {{-- Total spent badge --}}
            <div class="mb-spent">
                <span>{{ __('messages.total_spent') }}</span>
                <strong>{{ number_format($totalSpent, 0) }} MAD</strong>
            </div>
        </div>
    </div>

    <div class="mb-content">

        {{-- Success alert --}}
        @if(session('success'))
            <div class="mb-alert">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                {{ session('success') }}
            </div>
        @endif

        {{-- ══ UPCOMING ══ --}}
        <div class="mb-section">
            <div class="mb-section-header">
                <div class="mb-section-dot mb-section-dot--blue"></div>
                <h2>{{ __('messages.active_bookings') }}</h2>
                <span class="mb-section-count">{{ $upcoming->count() }}</span>
            </div>

            @forelse($upcoming as $booking)
                <div class="mb-card mb-card--upcoming">

                    {{-- Car image --}}
                    <div class="mb-card-img">
                        @if($booking->car)
                            <img src="{{ $booking->car->image_url }}"
                                 alt="{{ $booking->car->brand }} {{ $booking->car->model }}">
                        @else
                            <div class="mb-card-img-placeholder">
                                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/><path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/></svg>
                            </div>
                        @endif
                        <div class="mb-card-badge mb-card-badge--upcoming">
                            <span class="mb-pulse"></span>
                            {{ __('messages.status_confirmed') }}
                        </div>
                    </div>

                    {{-- Info --}}
                    <div class="mb-card-info">
                        <h3>{{ $booking->car->brand }} {{ $booking->car->model }}</h3>

                        <div class="mb-card-dates">
                            <div class="mb-date">
                                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/></svg>
                                {{ $booking->start_date->format('d M Y') }}
                            </div>
                            <svg class="mb-arrow" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            <div class="mb-date">
                                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/></svg>
                                {{ $booking->end_date->format('d M Y') }}
                            </div>
                        </div>

                        @if($booking->pickupLocation)
                            <div class="mb-card-loc">
                                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                                {{ $booking->pickupLocation->name }}
                            </div>
                        @endif

                        <div class="mb-card-ref">{{ __('messages.booking_reference') }}: #{{ $booking->reference ?? str_pad($booking->id, 6, '0', STR_PAD_LEFT) }}</div>
                    </div>

                    {{-- Price --}}
                    <div class="mb-card-right">
                        <div class="mb-card-price">
                            <span>{{ __('messages.total_price') }}</span>
                            <strong>{{ number_format($booking->total_amount, 0) }} MAD</strong>
                        </div>
                    </div>

                </div>
            @empty
                <div class="mb-empty">
                    <div class="mb-empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <p>{{ __('messages.no_upcoming_bookings') }}</p>
                    <a href="{{ route('cars.index') }}" class="mb-empty-btn">{{ __('messages.browse_cars') }}</a>
                </div>
            @endforelse
        </div>

        {{-- ══ PAST ══ --}}
        <div class="mb-section">
            <div class="mb-section-header">
                <div class="mb-section-dot mb-section-dot--muted"></div>
                <h2>{{ __('messages.past_bookings') }}</h2>
                <span class="mb-section-count">{{ $past->count() }}</span>
            </div>

            @forelse($past as $booking)
                <div class="mb-card mb-card--past">

                    {{-- Car image --}}
                    <div class="mb-card-img mb-card-img--past">
                        @if($booking->car)
                            <img src="{{ $booking->car->image_url }}"
                                 alt="{{ $booking->car->brand }} {{ $booking->car->model }}">
                        @else
                            <div class="mb-card-img-placeholder">
                                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/><path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/></svg>
                            </div>
                        @endif

                        {{-- Status badge --}}
                        <div class="mb-card-badge
                            @if($booking->status === 'completed') mb-card-badge--done
                            @elseif($booking->status === 'cancelled') mb-card-badge--cancelled
                            @else mb-card-badge--muted @endif">
                            @if($booking->status === 'completed')
                                {{ __('messages.status_completed') }}
                            @elseif($booking->status === 'cancelled')
                                {{ __('messages.status_cancelled') }}
                            @else
                                {{ ucfirst($booking->status) }}
                            @endif
                        </div>
                    </div>

                    {{-- Info --}}
                    <div class="mb-card-info">
                        <h3>{{ $booking->car->brand }} {{ $booking->car->model }}</h3>

                        <div class="mb-card-dates">
                            <div class="mb-date mb-date--muted">
                                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/></svg>
                                {{ $booking->start_date->format('d M Y') }}
                            </div>
                            <svg class="mb-arrow mb-arrow--muted" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            <div class="mb-date mb-date--muted">
                                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/></svg>
                                {{ $booking->end_date->format('d M Y') }}
                            </div>
                        </div>

                        <div class="mb-card-ref">{{ __('messages.booking_reference') }}: #{{ $booking->reference ?? str_pad($booking->id, 6, '0', STR_PAD_LEFT) }}</div>
                    </div>

                    {{-- Price + actions --}}
                    <div class="mb-card-right">
                        <div class="mb-card-price mb-card-price--muted">
                            <span>{{ __('messages.total_price') }}</span>
                            <strong>{{ number_format($booking->total_amount, 0) }} MAD</strong>
                        </div>

                        @if($booking->status === 'completed' && !$booking->review)
                            <a href="{{ route('reviews.create', $booking) }}" class="mb-review-btn">
                                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                {{ __('messages.leave_review') }}
                            </a>
                        @elseif($booking->review)
                            <div class="mb-reviewed">
                                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                {{ __('messages.review_submitted') }}
                            </div>
                        @endif
                    </div>

                </div>
            @empty
                <div class="mb-empty">
                    <div class="mb-empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <p>{{ __('messages.no_past_bookings') }}</p>
                    <a href="{{ route('cars.index') }}" class="mb-empty-btn">{{ __('messages.browse_cars') }}</a>
                </div>
            @endforelse

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

.mb-page { background: var(--bg); min-height: 100vh; }

/* Hero */
.mb-hero {
    background: linear-gradient(135deg, #0d0d0d, #110f08);
    border-bottom: 1px solid var(--b1);
    padding: 3rem 0 2.5rem;
}
.mb-hero-inner {
    max-width: 900px; margin: 0 auto;
    padding: 0 2rem;
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1.5rem;
    flex-wrap: wrap;
}
.mb-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    font-size: 0.7rem;
    font-weight: 700;
    color: var(--gold);
    letter-spacing: 0.12em;
    text-transform: uppercase;
    margin-bottom: 0.75rem;
}
.mb-eyebrow svg { width: 14px; height: 14px; }
.mb-hero-inner h1 {
    font-size: clamp(1.5rem, 3vw, 2rem);
    font-weight: 800;
    color: var(--text);
    letter-spacing: -0.04em;
    margin-bottom: 6px;
}
.mb-hero-inner > div > p { font-size: 0.875rem; color: var(--muted); }

/* Spent badge */
.mb-spent {
    background: rgba(200,157,102,0.08);
    border: 1px solid rgba(200,157,102,0.2);
    border-radius: 12px;
    padding: 1rem 1.4rem;
    text-align: right;
    flex-shrink: 0;
}
.mb-spent span { display: block; font-size: 0.65rem; color: var(--hint); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 4px; }
.mb-spent strong { font-size: 1.5rem; font-weight: 800; color: var(--gold); letter-spacing: -0.03em; }

/* Content */
.mb-content { max-width: 900px; margin: 0 auto; padding: 2rem; display: flex; flex-direction: column; gap: 2.5rem; }

/* Alert */
.mb-alert {
    display: flex; align-items: center; gap: 10px;
    background: rgba(74,222,128,0.08);
    border: 1px solid rgba(74,222,128,0.2);
    border-radius: 10px;
    padding: 12px 16px;
    font-size: 0.875rem;
    color: #4ade80;
    margin-bottom: 0.5rem;
}
.mb-alert svg { width: 18px; height: 18px; flex-shrink: 0; }

/* Section */
.mb-section { display: flex; flex-direction: column; gap: 10px; }

.mb-section-header {
    display: flex; align-items: center; gap: 10px;
    margin-bottom: 0.5rem;
}
.mb-section-header h2 { font-size: 1rem; font-weight: 700; color: var(--text); letter-spacing: -0.01em; }

.mb-section-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.mb-section-dot--blue  { background: #60a5fa; box-shadow: 0 0 8px rgba(96,165,250,0.4); animation: pulse 2s ease infinite; }
.mb-section-dot--muted { background: var(--hint); }
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.4} }

.mb-section-count {
    margin-left: auto;
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--hint);
    background: var(--b1);
    border: 1px solid var(--b2);
    padding: 2px 10px;
    border-radius: 100px;
}

/* Cards */
.mb-card {
    display: flex;
    align-items: stretch;
    gap: 0;
    background: var(--card);
    border: 1px solid var(--b1);
    border-radius: 12px;
    overflow: hidden;
    transition: border-color var(--tr), transform var(--tr);
    animation: fadeUp 0.35s ease both;
}
@keyframes fadeUp { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:none} }
.mb-card:hover { border-color: var(--b2); transform: translateY(-1px); }
.mb-card--upcoming { border-left: 2px solid rgba(96,165,250,0.4); }
.mb-card--past     { border-left: 2px solid var(--b2); }

/* Image */
.mb-card-img {
    width: 160px;
    flex-shrink: 0;
    position: relative;
    overflow: hidden;
    background: #111;
}
.mb-card-img img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
.mb-card:hover .mb-card-img img { transform: scale(1.05); }
.mb-card-img--past img { filter: grayscale(30%); }
.mb-card-img-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; }
.mb-card-img-placeholder svg { width: 32px; height: 32px; color: var(--hint); }

/* Badge */
.mb-card-badge {
    position: absolute; bottom: 8px; left: 8px;
    display: flex; align-items: center; gap: 5px;
    padding: 3px 10px;
    border-radius: 100px;
    font-size: 0.62rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}
.mb-pulse { width: 5px; height: 5px; border-radius: 50%; background: currentColor; animation: pulse 2s ease infinite; }
.mb-card-badge--upcoming  { background: rgba(96,165,250,0.15); border: 1px solid rgba(96,165,250,0.3); color: #60a5fa; }
.mb-card-badge--done      { background: rgba(74,222,128,0.12); border: 1px solid rgba(74,222,128,0.2); color: #4ade80; }
.mb-card-badge--cancelled { background: rgba(248,113,113,0.12); border: 1px solid rgba(248,113,113,0.2); color: #f87171; }
.mb-card-badge--muted     { background: rgba(255,255,255,0.05); border: 1px solid var(--b2); color: var(--muted); }

/* Card info */
.mb-card-info {
    flex: 1;
    padding: 1.1rem 1.25rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 6px;
    min-width: 0;
}
.mb-card-info h3 {
    font-size: 1rem;
    font-weight: 700;
    color: var(--text);
    letter-spacing: -0.02em;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.mb-card-dates { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.mb-date {
    display: flex; align-items: center; gap: 5px;
    font-size: 0.8rem; color: var(--text);
}
.mb-date svg { width: 13px; height: 13px; color: var(--gold); flex-shrink: 0; }
.mb-date--muted { color: var(--muted); }
.mb-date--muted svg { color: var(--hint); }

.mb-arrow { width: 12px; height: 12px; color: var(--hint); flex-shrink: 0; }
.mb-arrow--muted { color: var(--hint); opacity: 0.5; }

.mb-card-loc {
    display: flex; align-items: center; gap: 5px;
    font-size: 0.75rem; color: var(--muted);
}
.mb-card-loc svg { width: 12px; height: 12px; color: #f43f5e; flex-shrink: 0; }

.mb-card-ref { font-size: 0.7rem; color: var(--hint); }

/* Right side */
.mb-card-right {
    padding: 1.1rem 1.25rem;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    justify-content: center;
    gap: 10px;
    flex-shrink: 0;
    min-width: 140px;
}

.mb-card-price { text-align: right; }
.mb-card-price span { display: block; font-size: 0.62rem; color: var(--hint); text-transform: uppercase; letter-spacing: 0.07em; margin-bottom: 3px; }
.mb-card-price strong { font-size: 1.2rem; font-weight: 800; color: var(--gold); letter-spacing: -0.03em; }
.mb-card-price--muted strong { color: var(--muted); font-size: 1rem; }

/* Review buttons */
.mb-review-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    background: rgba(251,191,36,0.1);
    border: 1px solid rgba(251,191,36,0.25);
    border-radius: 8px;
    color: #fbbf24;
    font-size: 0.75rem;
    font-weight: 700;
    text-decoration: none;
    white-space: nowrap;
    transition: all var(--tr);
}
.mb-review-btn svg { width: 13px; height: 13px; }
.mb-review-btn:hover { background: rgba(251,191,36,0.18); border-color: rgba(251,191,36,0.4); }

.mb-reviewed {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 12px;
    background: rgba(74,222,128,0.08);
    border: 1px solid rgba(74,222,128,0.2);
    border-radius: 8px;
    color: #4ade80;
    font-size: 0.72rem;
    font-weight: 600;
    white-space: nowrap;
}
.mb-reviewed svg { width: 13px; height: 13px; }

/* Empty state */
.mb-empty {
    display: flex; flex-direction: column; align-items: center;
    text-align: center;
    padding: 3rem 2rem;
    background: var(--card);
    border: 1px solid var(--b1);
    border-radius: 12px;
    gap: 0.75rem;
}
.mb-empty-icon {
    width: 56px; height: 56px;
    border-radius: 14px;
    background: rgba(200,157,102,0.08);
    border: 1px solid rgba(200,157,102,0.15);
    display: flex; align-items: center; justify-content: center;
}
.mb-empty-icon svg { width: 26px; height: 26px; color: var(--gold); opacity: 0.6; }
.mb-empty p { font-size: 0.875rem; color: var(--muted); }
.mb-empty-btn {
    padding: 8px 20px;
    background: linear-gradient(135deg, var(--gold), var(--gold-d));
    color: #fff;
    border-radius: 8px;
    font-size: 0.82rem;
    font-weight: 700;
    text-decoration: none;
    transition: opacity var(--tr);
}
.mb-empty-btn:hover { opacity: 0.88; }

/* Responsive */
@media (max-width: 640px) {
    .mb-card { flex-direction: column; }
    .mb-card-img { width: 100%; height: 160px; }
    .mb-card-right { align-items: flex-start; flex-direction: row; justify-content: space-between; flex-wrap: wrap; }
    .mb-hero-inner { flex-direction: column; align-items: flex-start; }
    .mb-spent { text-align: left; }
    .mb-content { padding: 1.5rem; }
}

/* RTL */
[dir="rtl"] .mb-card--upcoming { border-left: none; border-right: 2px solid rgba(96,165,250,0.4); }
[dir="rtl"] .mb-card--past     { border-left: none; border-right: 2px solid var(--b2); }
[dir="rtl"] .mb-card-right { align-items: flex-start; }
[dir="rtl"] .mb-arrow { transform: scaleX(-1); }
[dir="rtl"] .mb-section-count { margin-left: 0; margin-right: auto; }
</style>

@endsection
