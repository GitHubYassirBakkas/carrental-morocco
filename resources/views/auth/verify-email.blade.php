@extends('layouts.auth')

@section('title', __('messages.verify_email'))

@section('styles')
<style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html, body {
        height: 100% !important; min-height: 100vh !important;
        width: 100% !important; max-width: 100% !important;
        margin: 0 !important; padding: 0 !important;
        background: #0a0a0a !important; overflow-x: hidden !important;
    }
    body > * { max-width: none !important; }
    .container, .wrapper, main, .main, #app, #main,
    .auth-container, .page-wrapper, .content-wrapper {
        max-width: 100% !important; width: 100% !important;
        padding: 0 !important; margin: 0 !important;
    }
</style>
@endsection

@section('content')

<div class="auth-centered">
    <div class="auth-card">

        {{-- Animated envelope icon --}}
        <div class="card-icon card-icon--pulse">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>

        <a href="{{ route('home') }}" class="logo-link">
            <div class="logo-icon">
                <svg viewBox="0 0 20 20" fill="currentColor">
                    <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                    <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/>
                </svg>
            </div>
            <div>
                <span class="logo-name">CarRental</span>
                <span class="logo-sub">Morocco</span>
            </div>
        </a>

        <div class="lang-switcher">
            <a href="{{ route('language.switch', 'fr') }}" class="lang-btn {{ app()->getLocale() === 'fr' ? 'lang-active' : '' }}">🇫🇷 FR</a>
            <a href="{{ route('language.switch', 'en') }}" class="lang-btn {{ app()->getLocale() === 'en' ? 'lang-active' : '' }}">🇬🇧 EN</a>
            <a href="{{ route('language.switch', 'ar') }}" class="lang-btn {{ app()->getLocale() === 'ar' ? 'lang-active' : '' }}">🇲🇦 AR</a>
        </div>

        <h1 class="card-title">{{ __('messages.verify_email') }}</h1>
        <p class="card-desc">{{ __('messages.verify_email_desc') }}</p>

        {{-- Email display --}}
        <div class="email-badge">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="width:16px;height:16px;color:#C89D66;flex-shrink:0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8"/>
            </svg>
            <span>{{ auth()->user()->email }}</span>
        </div>

        @if (session('status') == 'verification-link-sent')
            <div class="alert alert-success">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                {{ __('messages.verification_sent') }}
            </div>
        @endif

        {{-- Resend form --}}
        <form method="POST" action="{{ route('verification.send') }}" class="auth-form">
            @csrf
            <button type="submit" class="btn-submit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span>{{ __('messages.resend_verification') }}</span>
            </button>
        </form>

        {{-- Steps hint --}}
        <div class="verify-steps">
            <div class="verify-step">
                <div class="vstep-dot vstep-done">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                </div>
                <span>{{ __('messages.vstep_1') }}</span>
            </div>
            <div class="vstep-line"></div>
            <div class="verify-step">
                <div class="vstep-dot vstep-active">2</div>
                <span>{{ __('messages.vstep_2') }}</span>
            </div>
            <div class="vstep-line"></div>
            <div class="verify-step">
                <div class="vstep-dot">3</div>
                <span>{{ __('messages.vstep_3') }}</span>
            </div>
        </div>

        <div class="divider"><span>{{ __('messages.or') }}</span></div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-logout">
                {{ __('messages.logout') }}
            </button>
        </form>

    </div>
</div>

@include('auth._shared_styles')

<style>
    .email-badge {
        display: flex;
        align-items: center;
        gap: 8px;
        background: rgba(200,157,102,0.08);
        border: 1px solid rgba(200,157,102,0.2);
        border-radius: 100px;
        padding: 8px 16px;
        font-size: 0.8125rem;
        color: #C89D66;
        margin-bottom: 1.25rem;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .verify-steps {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 1.5rem 0 1rem;
        padding: 1rem;
        background: rgba(255,255,255,0.02);
        border: 1px solid #1e1e1e;
        border-radius: 10px;
    }

    .verify-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        flex: 1;
    }

    .verify-step span {
        font-size: 0.65rem;
        color: #555;
        text-align: center;
        line-height: 1.3;
    }

    .vstep-dot {
        width: 28px; height: 28px;
        border-radius: 50%;
        background: #1a1a1a;
        border: 1px solid #333;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.7rem;
        color: #555;
        font-weight: 600;
    }

    .vstep-done {
        background: rgba(200,157,102,0.15);
        border-color: rgba(200,157,102,0.4);
        color: #C89D66;
    }

    .vstep-done svg { width: 14px; height: 14px; }

    .vstep-active {
        background: rgba(200,157,102,0.1);
        border-color: #C89D66;
        color: #C89D66;
        animation: vstep-pulse 2s ease infinite;
    }

    @keyframes vstep-pulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(200,157,102,0.3); }
        50%       { box-shadow: 0 0 0 6px rgba(200,157,102,0); }
    }

    .vstep-line {
        flex: 0 0 20px;
        height: 1px;
        background: #222;
        margin-bottom: 20px;
    }

    .btn-logout {
        display: block;
        width: 100%;
        padding: 0.75rem;
        background: transparent;
        border: 1px solid #2a2a2a;
        border-radius: 8px;
        color: #666;
        font-size: 0.875rem;
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
    }

    .btn-logout:hover {
        border-color: #ef4444;
        color: #ef4444;
        background: rgba(239,68,68,0.05);
    }

    .card-icon--pulse {
        animation: icon-float 3s ease-in-out infinite;
    }

    @keyframes icon-float {
        0%, 100% { transform: translateY(0); }
        50%       { transform: translateY(-6px); }
    }
</style>
@endsection