@extends('layouts.app')

@section('title', __('messages.leave_review'))

@section('content')

<div class="rv-page">

    <div class="rv-wrap">

        {{-- Back link --}}
        <a href="{{ route('my_booking.index') }}" class="rv-back">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            {{ __('messages.my_bookings') }}
        </a>

        {{-- Header --}}
        <div class="rv-header">
            <div class="rv-header-icon">
                <svg viewBox="0 0 20 20" fill="currentColor">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
            </div>
            <div>
                <h1>{{ __('messages.leave_review') }}</h1>
                <p>{{ __('messages.review_subtitle') }} {{ $booking->car->brand }} {{ $booking->car->model }}</p>
            </div>
        </div>

        {{-- Booking summary card --}}
        <div class="rv-booking">
            <div class="rv-booking-img">
                @if($booking->car)
                    <img src="{{ $booking->car->image_url }}"
                         alt="{{ $booking->car->brand }} {{ $booking->car->model }}">
                @else
                    <div class="rv-booking-img-placeholder">
                        <svg viewBox="0 0 20 20" fill="currentColor"><path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/><path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/></svg>
                    </div>
                @endif
            </div>

            <div class="rv-booking-info">
                <h3>{{ $booking->car->brand }} {{ $booking->car->model }}</h3>
                <div class="rv-booking-dates">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/></svg>
                    {{ $booking->start_date->format('d M Y') }} → {{ $booking->end_date->format('d M Y') }}
                </div>
                <div class="rv-booking-days">
                    {{ $booking->total_days }} {{ $booking->total_days == 1 ? __('messages.total_days_singular') : __('messages.total_days') }}
                </div>
            </div>

            <div class="rv-booking-price">
                <span>{{ __('messages.total_price') }}</span>
                <strong>{{ number_format($booking->total_amount, 0) }} MAD</strong>
            </div>
        </div>

        {{-- Review form --}}
        <form action="{{ route('reviews.store', $booking) }}" method="POST" class="rv-form">
            @csrf

            {{-- Star rating --}}
            <div class="rv-field">
                <label>{{ __('messages.review_rating') }} <span>*</span></label>

                <div class="rv-stars" id="star-rating">
                    @for($i = 1; $i <= 5; $i++)
                        <button type="button" class="rv-star" data-rating="{{ $i }}">
                            <svg viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        </button>
                    @endfor
                </div>

                <div class="rv-rating-label" id="ratingLabel">{{ __('messages.review_select_rating') }}</div>

                <input type="hidden" name="rating" id="rating-input" value="{{ old('rating') }}">

                @error('rating')
                    <p class="rv-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- Comment --}}
            <div class="rv-field">
                <label for="comment">{{ __('messages.review_comment') }} <span>*</span></label>
                <textarea
                    name="comment"
                    id="comment"
                    rows="5"
                    placeholder="{{ __('messages.review_placeholder') }}"
                >{{ old('comment') }}</textarea>

                @error('comment')
                    <p class="rv-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- Actions --}}
            <div class="rv-actions">
                <button type="submit" class="rv-submit">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    {{ __('messages.review_submit') }}
                </button>
                <a href="{{ route('my_booking.index') }}" class="rv-cancel">
                    {{ __('messages.cancel') }}
                </a>
            </div>

        </form>

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

.rv-page {
    background: var(--bg);
    min-height: 100vh;
    padding: 2.5rem 1rem;
    display: flex;
    align-items: flex-start;
    justify-content: center;
}

.rv-wrap {
    width: 100%;
    max-width: 580px;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    animation: fadeUp 0.4s ease both;
}

@keyframes fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:none} }

/* Back */
.rv-back {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.82rem;
    color: var(--muted);
    text-decoration: none;
    transition: color var(--tr);
}
.rv-back svg { width: 15px; height: 15px; transition: transform var(--tr); }
.rv-back:hover { color: var(--gold); }
.rv-back:hover svg { transform: translateX(-3px); }

/* Header */
.rv-header {
    display: flex;
    align-items: center;
    gap: 14px;
    padding-bottom: 1.25rem;
    border-bottom: 1px solid var(--b1);
}

.rv-header-icon {
    width: 48px; height: 48px;
    border-radius: 12px;
    background: rgba(251,191,36,0.1);
    border: 1px solid rgba(251,191,36,0.2);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    color: #fbbf24;
}
.rv-header-icon svg { width: 24px; height: 24px; }

.rv-header h1 {
    font-size: 1.4rem;
    font-weight: 800;
    color: var(--text);
    letter-spacing: -0.03em;
    margin-bottom: 3px;
}

.rv-header p { font-size: 0.82rem; color: var(--muted); }

/* Booking card */
.rv-booking {
    display: flex;
    align-items: center;
    gap: 14px;
    background: var(--card);
    border: 1px solid var(--b1);
    border-radius: 12px;
    padding: 1rem 1.1rem;
    overflow: hidden;
}

.rv-booking-img {
    width: 90px; height: 68px;
    border-radius: 8px;
    overflow: hidden;
    background: #111;
    flex-shrink: 0;
    border: 1px solid var(--b1);
}
.rv-booking-img img { width: 100%; height: 100%; object-fit: cover; }
.rv-booking-img-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; }
.rv-booking-img-placeholder svg { width: 24px; height: 24px; color: var(--hint); }

.rv-booking-info { flex: 1; min-width: 0; }
.rv-booking-info h3 {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 4px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

.rv-booking-dates {
    display: flex; align-items: center; gap: 5px;
    font-size: 0.75rem; color: var(--muted);
    margin-bottom: 3px;
}
.rv-booking-dates svg { width: 12px; height: 12px; color: var(--gold); flex-shrink: 0; }

.rv-booking-days { font-size: 0.7rem; color: var(--hint); }

.rv-booking-price { text-align: right; flex-shrink: 0; }
.rv-booking-price span { display: block; font-size: 0.62rem; color: var(--hint); text-transform: uppercase; letter-spacing: 0.07em; margin-bottom: 3px; }
.rv-booking-price strong { font-size: 1.1rem; font-weight: 800; color: var(--gold); letter-spacing: -0.03em; }

/* Form */
.rv-form {
    background: var(--card);
    border: 1px solid var(--b1);
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.rv-field { display: flex; flex-direction: column; gap: 0.5rem; }

.rv-field label {
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.08em;
}
.rv-field label span { color: #f87171; }

/* Stars */
.rv-stars {
    display: flex;
    gap: 6px;
}

.rv-star {
    background: none;
    border: none;
    cursor: pointer;
    padding: 3px;
    transition: transform 0.15s ease;
}
.rv-star:hover { transform: scale(1.15); }
.rv-star svg { width: 36px; height: 36px; color: var(--b2); transition: color 0.15s; display: block; }
.rv-star.rv-star--on svg { color: #fbbf24; }
.rv-star.rv-star--hover svg { color: #fcd34d; }

.rv-rating-label {
    font-size: 0.78rem;
    color: var(--hint);
    font-style: italic;
    min-height: 20px;
    transition: color 0.2s;
}

/* Textarea */
.rv-field textarea {
    width: 100% !important;
    background: #111 !important;
    background-color: #111 !important;
    border: 1px solid var(--b2) !important;
    border-radius: 10px !important;
    color: var(--text) !important;
    font-size: 0.9rem !important;
    font-family: inherit !important;
    padding: 12px 14px !important;
    outline: none !important;
    resize: vertical !important;
    min-height: 130px !important;
    -webkit-text-fill-color: var(--text) !important;
    caret-color: var(--gold) !important;
    line-height: 1.7 !important;
    transition: border-color var(--tr), box-shadow var(--tr);
}
.rv-field textarea:-webkit-autofill {
    -webkit-box-shadow: 0 0 0 1000px #111 inset !important;
    -webkit-text-fill-color: var(--text) !important;
}
.rv-field textarea::placeholder { color: var(--hint) !important; }
.rv-field textarea:focus {
    border-color: var(--gold) !important;
    box-shadow: 0 0 0 3px rgba(200,157,102,0.1) !important;
}

.rv-error { font-size: 0.75rem; color: #f87171; }

/* Actions */
.rv-actions { display: flex; gap: 10px; }

.rv-submit {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 0.875rem;
    background: linear-gradient(135deg, var(--gold), var(--gold-d));
    border: none;
    border-radius: 10px;
    color: #fff;
    font-size: 0.9rem;
    font-weight: 700;
    cursor: pointer;
    transition: opacity var(--tr), box-shadow var(--tr), transform 0.15s;
    box-shadow: 0 4px 16px rgba(200,157,102,0.22);
}
.rv-submit svg { width: 16px; height: 16px; }
.rv-submit:hover { opacity: 0.9; box-shadow: 0 6px 24px rgba(200,157,102,0.38); }
.rv-submit:active { transform: scale(0.985); }

.rv-cancel {
    padding: 0.875rem 1.25rem;
    background: transparent;
    border: 1px solid var(--b2);
    border-radius: 10px;
    color: var(--muted);
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    transition: all var(--tr);
    display: flex; align-items: center;
}
.rv-cancel:hover { border-color: var(--b2); color: var(--text); background: rgba(255,255,255,0.03); }

/* RTL */
[dir="rtl"] .rv-back svg { transform: scaleX(-1); }
[dir="rtl"] .rv-back:hover svg { transform: scaleX(-1) translateX(3px); }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const stars       = document.querySelectorAll('.rv-star');
    const ratingInput = document.getElementById('rating-input');
    const ratingLabel = document.getElementById('ratingLabel');

    const labels = {
        1: '{{ __("messages.review_label_1") }}',
        2: '{{ __("messages.review_label_2") }}',
        3: '{{ __("messages.review_label_3") }}',
        4: '{{ __("messages.review_label_4") }}',
        5: '{{ __("messages.review_label_5") }}',
    };

    let selected = parseInt(ratingInput.value) || 0;
    if (selected) paint(selected, 'on');

    stars.forEach(star => {
        const n = parseInt(star.dataset.rating);

        star.addEventListener('mouseenter', () => {
            paint(n, 'hover');
            ratingLabel.textContent = labels[n];
            ratingLabel.style.color = '#fbbf24';
        });

        star.addEventListener('click', () => {
            selected = n;
            ratingInput.value = n;
            paint(n, 'on');
            ratingLabel.textContent = labels[n];
            ratingLabel.style.color = '#fbbf24';
        });
    });

    document.getElementById('star-rating').addEventListener('mouseleave', () => {
        paint(selected, 'on');
        ratingLabel.textContent = selected ? labels[selected] : '{{ __("messages.review_select_rating") }}';
        ratingLabel.style.color = selected ? '#fbbf24' : '';
    });

    function paint(n, mode) {
        stars.forEach((s, i) => {
            s.classList.remove('rv-star--on', 'rv-star--hover');
            if (i < n) s.classList.add(mode === 'hover' ? 'rv-star--hover' : 'rv-star--on');
        });
    }
});
</script>

@endsection
