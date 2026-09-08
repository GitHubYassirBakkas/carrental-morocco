@extends('layouts.auth')

@section('title', __('messages.register'))

@section('styles')
<style>
    :root {
        --gold: #C89D66;
        --gold-dark: #9d7245;
        --bg: #0a0a0a;
        --panel: #111111;
        --border: rgba(255,255,255,0.1);
        --text-primary: #f5f5f5;
        --text-muted: #a3a3a3;
        --text-hint: #737373;
        --transition: 180ms ease;
    }

    html, body {
        min-height: 100vh !important;
        width: 100% !important;
        margin: 0 !important;
        overflow-x: hidden !important;
        background: var(--bg) !important;
    }

    .auth-root {
        min-height: 100vh;
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(360px, 460px);
        background:
            linear-gradient(120deg, rgba(200,157,102,0.08), transparent 35%),
            #0a0a0a;
    }

    .auth-left {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 48px 24px;
    }

    .form-inner {
        width: min(100%, 560px);
    }

    .logo-link {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 28px;
        color: var(--text-primary);
        text-decoration: none;
    }

    .logo-icon {
        width: 42px;
        height: 42px;
        border-radius: 8px;
        display: grid;
        place-items: center;
        color: #111;
        background: var(--gold);
    }

    .logo-icon svg { width: 24px; height: 24px; }
    .logo-name { display: block; font-weight: 800; line-height: 1; }
    .logo-sub { color: var(--gold); font-size: 0.82rem; }

    .lang-switcher {
        display: flex;
        gap: 6px;
        margin-bottom: 24px;
    }

    .lang-btn {
        padding: 5px 12px;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 700;
        text-decoration: none;
        color: var(--text-hint);
        border: 1px solid #2a2a2a;
        transition: all var(--transition);
    }

    .lang-btn:hover,
    .lang-active {
        border-color: rgba(200,157,102,0.45);
        color: var(--gold);
        background: rgba(200,157,102,0.12);
    }

    .form-title {
        color: var(--text-primary);
        font-size: clamp(2rem, 5vw, 3.1rem);
        line-height: 1;
        margin-bottom: 12px;
        letter-spacing: 0;
    }

    .form-subtitle {
        color: var(--text-muted);
        margin-bottom: 28px;
        line-height: 1.6;
    }

    .alert {
        display: flex;
        gap: 10px;
        padding: 14px 16px;
        border-radius: 8px;
        margin-bottom: 18px;
        color: var(--text-primary);
        border: 1px solid var(--border);
    }

    .alert svg {
        width: 20px;
        height: 20px;
        flex: 0 0 auto;
    }

    .alert-success {
        background: rgba(16,185,129,0.12);
        border-color: rgba(16,185,129,0.35);
    }

    .alert-error {
        background: rgba(239,68,68,0.12);
        border-color: rgba(239,68,68,0.35);
    }

    .auth-form {
        display: grid;
        gap: 16px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .field label {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        color: var(--text-primary);
        font-size: 0.88rem;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .optional {
        color: var(--text-hint);
        font-size: 0.74rem;
        font-weight: 600;
    }

    .input-wrap {
        position: relative;
    }

    .field-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        width: 20px;
        height: 20px;
        color: var(--text-hint);
        pointer-events: none;
    }

    .input-wrap input {
        width: 100%;
        min-height: 46px;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: rgba(255,255,255,0.045);
        color: var(--text-primary);
        padding: 11px 44px;
        outline: none;
        transition: border-color var(--transition), background var(--transition);
    }

    .input-wrap input:focus {
        border-color: rgba(200,157,102,0.65);
        background: rgba(255,255,255,0.07);
    }

    .toggle-pw {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        width: 32px;
        height: 32px;
        border: 0;
        background: transparent;
        color: var(--text-hint);
        cursor: pointer;
    }

    .toggle-pw svg {
        width: 20px;
        height: 20px;
    }

    .pw-strength {
        height: 4px;
        border-radius: 999px;
        background: rgba(255,255,255,0.08);
        margin-top: 8px;
        overflow: hidden;
    }

    .pw-bar {
        width: 0;
        height: 100%;
        transition: width var(--transition), background var(--transition);
    }

    .pw-hint {
        min-height: 18px;
        margin-top: 4px;
        font-size: 0.78rem;
    }

    .terms-row {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        color: var(--text-muted);
        font-size: 0.88rem;
        line-height: 1.5;
    }

    .terms-row input {
        margin-top: 4px;
        accent-color: var(--gold);
    }

    .terms-row a,
    .auth-link {
        color: var(--gold);
        text-decoration: none;
    }

    .terms-row a:hover,
    .auth-link:hover {
        text-decoration: underline;
    }

    .btn-submit {
        min-height: 48px;
        border: 0;
        border-radius: 8px;
        background: var(--gold);
        color: #111;
        font-weight: 800;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: transform var(--transition), background var(--transition);
    }

    .btn-submit:hover {
        background: var(--gold-dark);
        transform: translateY(-1px);
    }

    .btn-submit svg {
        width: 18px;
        height: 18px;
    }

    .login-line {
        color: var(--text-muted);
        text-align: center;
        margin-top: 8px;
    }

    .auth-right {
        min-height: 100vh;
        display: flex;
        align-items: flex-end;
        padding: 44px;
        background:
            linear-gradient(180deg, rgba(10,10,10,0.12), rgba(10,10,10,0.86)),
            url("{{ asset('images/hero/Mercedes.jpg') }}");
        background-size: cover;
        background-position: center;
        border-left: 1px solid var(--border);
    }

    .auth-right-content {
        max-width: 360px;
    }

    .auth-right-content h2 {
        color: var(--text-primary);
        font-size: 2rem;
        line-height: 1.1;
        margin-bottom: 12px;
        letter-spacing: 0;
    }

    .auth-right-content p {
        color: rgba(245,245,245,0.78);
        line-height: 1.7;
    }

    [dir="rtl"] .field-icon { left: auto; right: 14px; }
    [dir="rtl"] .input-wrap input { padding-left: 44px; padding-right: 44px; text-align: right; }
    [dir="rtl"] .toggle-pw { right: auto; left: 10px; }

    @media (max-width: 980px) {
        .auth-root { grid-template-columns: 1fr; }
        .auth-right { display: none; }
    }

    @media (max-width: 640px) {
        .auth-left { padding: 28px 16px; }
        .form-grid { grid-template-columns: 1fr; }
        .form-title { font-size: 2.2rem; }
    }
</style>
@endsection

@section('content')
<div class="auth-root" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <div class="auth-left">
        <div class="form-inner">
            <a href="{{ route('home') }}" class="logo-link">
                <div class="logo-icon">
                    <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
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
                <a href="{{ route('language.switch', 'en') }}" class="lang-btn {{ app()->getLocale() === 'en' ? 'lang-active' : '' }}">EN</a>
                <a href="{{ route('language.switch', 'fr') }}" class="lang-btn {{ app()->getLocale() === 'fr' ? 'lang-active' : '' }}">FR</a>
                <a href="{{ route('language.switch', 'ar') }}" class="lang-btn {{ app()->getLocale() === 'ar' ? 'lang-active' : '' }}">AR</a>
            </div>

            <h1 class="form-title">{{ __('messages.register_title') }}</h1>
            <p class="form-subtitle">{{ __('messages.register_driver_later') }}</p>

            @if (session('success'))
                <div class="alert alert-success">
                    <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.7-9.3a1 1 0 00-1.4-1.4L9 10.6 7.7 9.3a1 1 0 00-1.4 1.4l2 2a1 1 0 001.4 0l4-4z" clip-rule="evenodd"/></svg>
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-error">
                    <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M18 10A8 8 0 112 10a8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    <div>
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}" class="auth-form">
                @csrf

                <div class="field">
                    <label for="name">{{ __('messages.full_name') }}</label>
                    <div class="input-wrap">
                        <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="{{ __('messages.name_placeholder') }}">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label for="email">{{ __('messages.email') }}</label>
                        <div class="input-wrap">
                            <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.2A9 9 0 0112 21"/>
                            </svg>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" placeholder="you@example.com">
                        </div>
                    </div>

                    <div class="field">
                        <label for="phone">{{ __('messages.phone') }}</label>
                        <div class="input-wrap">
                            <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.3a1 1 0 011 .7l1.3 4a1 1 0 01-.5 1.2L8 10a11 11 0 005.5 5.5l1.1-2.2a1 1 0 011.2-.5l4 1.3a1 1 0 01.7 1V19a2 2 0 01-2 2h-1C9.7 21 3 14.3 3 6V5z"/>
                            </svg>
                            <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" required autocomplete="tel" placeholder="+212 6XX XXX XXX">
                        </div>
                    </div>
                </div>

                <div class="field">
                    <label for="address">{{ __('messages.address') }} <span class="optional">{{ __('messages.optional') }}</span></label>
                    <div class="input-wrap">
                        <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 11l9-8 9 8M5 10v10h14V10"/>
                        </svg>
                        <input id="address" name="address" type="text" value="{{ old('address') }}" autocomplete="street-address" placeholder="{{ __('messages.street_address_placeholder') }}">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label for="city">{{ __('messages.city') }} <span class="optional">{{ __('messages.optional') }}</span></label>
                        <div class="input-wrap">
                            <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 21V5a2 2 0 012-2h8a2 2 0 012 2v16M4 21h16M9 7h2m-2 4h2m-2 4h2"/>
                            </svg>
                            <input id="city" name="city" type="text" value="{{ old('city') }}" autocomplete="address-level2" placeholder="Meknes">
                        </div>
                    </div>

                    <div class="field">
                        <label for="country">{{ __('messages.country') }} <span class="optional">{{ __('messages.optional') }}</span></label>
                        <div class="input-wrap">
                            <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18zM3.6 9h16.8M3.6 15h16.8M12 3a14 14 0 010 18M12 3a14 14 0 000 18"/>
                            </svg>
                            <input id="country" name="country" type="text" value="{{ old('country', 'Morocco') }}" autocomplete="country-name">
                        </div>
                    </div>
                </div>

                <div class="field">
                    <label for="postal_code">{{ __('messages.postal_code') }} <span class="optional">{{ __('messages.optional') }}</span></label>
                    <div class="input-wrap">
                        <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h8"/>
                        </svg>
                        <input id="postal_code" name="postal_code" type="text" value="{{ old('postal_code') }}" autocomplete="postal-code" placeholder="20000">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label for="password">{{ __('messages.password') }}</label>
                        <div class="input-wrap">
                            <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            <input id="password" name="password" type="password" required autocomplete="new-password" placeholder="********">
                            <button type="button" class="toggle-pw" onclick="togglePassword('password','eye-show','eye-hide')" aria-label="{{ __('messages.toggle_password_visibility') }}">
                                <svg id="eye-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12C3.7 8 7.5 5 12 5s8.3 3 9.5 7c-1.2 4-5 7-9.5 7s-8.3-3-9.5-7z"/>
                                </svg>
                                <svg id="eye-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="display:none" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.6 10.6A2 2 0 0012 14a2 2 0 001.4-.6M9.9 5.2A10 10 0 0112 5c4.5 0 8.3 3 9.5 7a10.7 10.7 0 01-3 4.4M6.1 6.8A10.7 10.7 0 002.5 12c1.2 4 5 7 9.5 7a10 10 0 003.1-.5"/>
                                </svg>
                            </button>
                        </div>
                        <div class="pw-strength"><div class="pw-bar" id="pw-bar"></div></div>
                        <p class="pw-hint" id="pw-hint"></p>
                    </div>

                    <div class="field">
                        <label for="password_confirmation">{{ __('messages.confirm_password') }}</label>
                        <div class="input-wrap">
                            <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.6-4A12 12 0 0112 3 12 12 0 013.4 6 12 12 0 003 9c0 5.6 3.8 10.3 9 11.6 5.2-1.3 9-6 9-11.6 0-1-.1-2-.4-3z"/>
                            </svg>
                            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" placeholder="********">
                        </div>
                    </div>
                </div>

                <label class="terms-row">
                    <input type="checkbox" name="terms" required>
                    <span>{{ __('messages.agree_terms_privacy') }}</span>
                </label>

                <button type="submit" class="btn-submit">
                    {{ __('messages.create_account') }}
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 5l7 7-7 7"/>
                    </svg>
                </button>

                <p class="login-line">
                    {{ __('messages.already_account') }}
                    <a class="auth-link" href="{{ route('login') }}">{{ __('messages.sign_in') }}</a>
                </p>
            </form>
        </div>
    </div>

    <aside class="auth-right">
        <div class="auth-right-content">
            <h2>{{ __('messages.register_side_title') }}</h2>
            <p>{{ __('messages.register_side_desc') }}</p>
        </div>
    </aside>
</div>

<script>
    function togglePassword(inputId, showId, hideId) {
        const input = document.getElementById(inputId);
        const eyeShow = document.getElementById(showId);
        const eyeHide = document.getElementById(hideId);
        const isHidden = input.type === 'password';

        input.type = isHidden ? 'text' : 'password';
        eyeShow.style.display = isHidden ? 'none' : 'block';
        eyeHide.style.display = isHidden ? 'block' : 'none';
    }

    document.getElementById('password')?.addEventListener('input', function () {
        const value = this.value;
        const bar = document.getElementById('pw-bar');
        const hint = document.getElementById('pw-hint');
        let score = 0;

        if (value.length >= 8) score++;
        if (/[A-Z]/.test(value)) score++;
        if (/[0-9]/.test(value)) score++;
        if (/[^A-Za-z0-9]/.test(value)) score++;

        const levels = [
            { width: '0%', color: 'transparent', label: '' },
            { width: '25%', color: '#ef4444', label: '{{ __("messages.pw_weak") }}' },
            { width: '50%', color: '#f59e0b', label: '{{ __("messages.pw_fair") }}' },
            { width: '75%', color: '#3b82f6', label: '{{ __("messages.pw_good") }}' },
            { width: '100%', color: '#10b981', label: '{{ __("messages.pw_strong") }}' },
        ];

        bar.style.width = levels[score].width;
        bar.style.background = levels[score].color;
        hint.textContent = levels[score].label;
        hint.style.color = levels[score].color;
    });
</script>
@endsection
