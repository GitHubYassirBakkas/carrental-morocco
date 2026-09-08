@extends('layouts.auth')

@section('title', __('messages.register'))

@section('styles')
<style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    html, body {
        height: 100% !important;
        min-height: 100vh !important;
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow-x: hidden !important;
        background: #0a0a0a !important;
    }

    body > * { max-width: none !important; }
    .container, .wrapper, main, .main, #app, #main,
    .auth-container, .page-wrapper, .content-wrapper {
        max-width: 100% !important;
        width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }
</style>
@endsection

@section('content')

<div class="auth-root">

    {{-- ═══════════════════════════════════════
         LEFT — Register Form
    ═══════════════════════════════════════ --}}
    <div class="auth-left">
        <div class="form-inner">

            {{-- Logo --}}
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

            {{-- Language Switcher --}}
            <div class="lang-switcher">
                <a href="{{ route('language.switch', 'fr') }}"
                   class="lang-btn {{ app()->getLocale() === 'fr' ? 'lang-active' : '' }}">
                    🇫🇷 FR
                </a>
                <a href="{{ route('language.switch', 'en') }}"
                   class="lang-btn {{ app()->getLocale() === 'en' ? 'lang-active' : '' }}">
                    🇬🇧 EN
                </a>
                <a href="{{ route('language.switch', 'ar') }}"
                   class="lang-btn {{ app()->getLocale() === 'ar' ? 'lang-active' : '' }}">
                    🇲🇦 AR
                </a>
            </div>

            <h1 class="form-title">{{ __('messages.register_title') }}</h1>
            <p class="form-subtitle">{{ __('messages.register_subtitle') }}</p>

            {{-- Alerts --}}
            @if (session('success'))
                <div class="alert alert-success">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-error">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    <div>
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Register Form --}}
            <form method="POST" action="{{ route('register') }}" class="auth-form">
                @csrf

                {{-- Name --}}
                <div class="field">
                    <label for="name">{{ __('messages.name') }}</label>
                    <div class="input-wrap">
                        <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="{{ old('name') }}"
                            required
                            autofocus
                            placeholder="{{ __('messages.name_placeholder') }}"
                            autocomplete="name"
                        >
                    </div>
                </div>

                {{-- Email --}}
                <div class="field">
                    <label for="email">{{ __('messages.email') }}</label>
                    <div class="input-wrap">
                        <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                        </svg>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            placeholder="your@email.com"
                            autocomplete="email"
                        >
                    </div>
                </div>

                {{-- Password --}}
                <div class="field">
                    <label for="password">{{ __('messages.password') }}</label>
                    <div class="input-wrap">
                        <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            placeholder="••••••••"
                            autocomplete="new-password"
                        >
                        <button type="button" class="toggle-pw" onclick="togglePassword('password','eye-show','eye-hide')" tabindex="-1">
                            <svg id="eye-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg id="eye-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="display:none">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                    {{-- Password strength indicator --}}
                    <div class="pw-strength" id="pw-strength">
                        <div class="pw-bar" id="pw-bar"></div>
                    </div>
                    <p class="pw-hint" id="pw-hint"></p>
                </div>

                {{-- Confirm Password --}}
                <div class="field">
                    <label for="password_confirmation">{{ __('messages.confirm_password') }}</label>
                    <div class="input-wrap">
                        <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            required
                            placeholder="••••••••"
                            autocomplete="new-password"
                        >
                        <button type="button" class="toggle-pw" onclick="togglePassword('password_confirmation','eye-show2','eye-hide2')" tabindex="-1">
                            <svg id="eye-show2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg id="eye-hide2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="display:none">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Terms --}}
                <div class="terms-row">
                    <label class="terms-label">
                        <input type="checkbox" name="terms" required class="terms-check">
                        <span>
                            {{ __('messages.agree_terms') }}
                            <a href="#" class="terms-link">{{ __('messages.terms_conditions') }}</a>
                            {{ __('messages.and') }}
                            <a href="#" class="terms-link">{{ __('messages.privacy_policy') }}</a>
                        </span>
                    </label>
                </div>

                {{-- Submit --}}
                <button type="submit" class="btn-submit">
                    <span>{{ __('messages.register') }}</span>
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </form>

            <div class="divider"><span>{{ __('messages.or') }}</span></div>

            <p class="register-text">
                {{ __('messages.already_account') }}
                <a href="{{ route('login') }}">{{ __('messages.login') }}</a>
            </p>

            <a href="{{ route('home') }}" class="back-home">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                {{ __('messages.back_to_home') }}
            </a>

        </div>
    </div>

    {{-- ═══════════════════════════════════════
         RIGHT — Brand Panel (desktop only)
    ═══════════════════════════════════════ --}}
    <div class="auth-right">
        <div class="auth-right-overlay"></div>
        <div class="auth-right-bg" style="background-image: url('{{ asset('images/hero/Mercedes.jpg') }}')"></div>

        <div class="auth-right-content">
            <div class="brand-pill">{{ __('messages.register_join') }}</div>

            <h2 class="brand-heading">{{ __('messages.welcome_message') }}</h2>
            <p class="brand-desc">{{ __('messages.feature_1_desc') }}</p>

            {{-- Steps --}}
            <div class="steps">
                <div class="step">
                    <div class="step-num">01</div>
                    <div>
                        <strong>{{ __('messages.step_1_title') }}</strong>
                        <span>{{ __('messages.step_1_desc') }}</span>
                    </div>
                </div>
                <div class="step">
                    <div class="step-num">02</div>
                    <div>
                        <strong>{{ __('messages.step_2_title') }}</strong>
                        <span>{{ __('messages.step_2_desc') }}</span>
                    </div>
                </div>
                <div class="step">
                    <div class="step-num">03</div>
                    <div>
                        <strong>{{ __('messages.step_3_title') }}</strong>
                        <span>{{ __('messages.step_3_desc') }}</span>
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="stats">
                <div class="stat">
                    <strong>500+</strong>
                    <span>{{ __('messages.stat_cars') }}</span>
                </div>
                <div class="stat">
                    <strong>10K+</strong>
                    <span>{{ __('messages.stat_customers') }}</span>
                </div>
                <div class="stat">
                    <strong>24/7</strong>
                    <span>{{ __('messages.stat_support') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- MOBILE banner --}}
    <div class="mobile-banner">
        <h2>{{ __('messages.register_title') }}</h2>
        <div class="mobile-stats">
            <div><strong>500+</strong><span>{{ __('messages.stat_cars') }}</span></div>
            <div><strong>10K+</strong><span>{{ __('messages.stat_customers') }}</span></div>
            <div><strong>24/7</strong><span>{{ __('messages.stat_support') }}</span></div>
        </div>
    </div>

</div>

{{-- ═══════════════════════════════════════════════════════════════
     STYLES
═══════════════════════════════════════════════════════════════ --}}
<style>
    :root {
        --gold:        #C89D66;
        --gold-light:  #D4AB76;
        --gold-dark:   #B8935E;
        --bg-page:     #0a0a0a;
        --bg-input:    #141414;
        --border:      #2a2a2a;
        --text-primary:#f0f0f0;
        --text-muted:  #888;
        --text-hint:   #555;
        --radius-sm:   8px;
        --radius-md:   12px;
        --transition:  0.2s ease;
    }

    /* ── Root layout ── */
    .auth-root {
        display: flex !important;
        min-height: 100vh !important;
        width: 100% !important;
        background: var(--bg-page) !important;
    }

    /* ── LEFT ── */
    .auth-left {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 1.5rem;
        background: var(--bg-page);
        order: 2;
        min-height: 100vh;
    }

    @media (min-width: 1024px) {
        .auth-left  { width: 50%; order: 1; padding: 3rem; }
        .auth-right { display: flex !important; }
        .mobile-banner { display: none !important; }
    }

    .form-inner {
        width: 100%;
        max-width: 420px;
        animation: fadeUp 0.5s ease both;
    }

    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(16px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* ── Logo ── */
    .logo-link {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        margin-bottom: 1.5rem;
    }

    .logo-icon {
        width: 46px; height: 46px;
        background: linear-gradient(135deg, var(--gold), var(--gold-dark));
        border-radius: var(--radius-sm);
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 4px 20px rgba(200,157,102,0.25);
        flex-shrink: 0;
    }

    .logo-icon svg { width: 24px; height: 24px; color: #fff; }

    .logo-name {
        display: block;
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--gold);
        letter-spacing: -0.02em;
    }

    .logo-sub {
        display: block;
        font-size: 0.7rem;
        color: var(--text-hint);
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    /* ── Lang switcher ── */
    .lang-switcher {
        display: flex;
        gap: 6px;
        margin-bottom: 1.25rem;
    }

    .lang-btn {
        padding: 5px 12px;
        border-radius: 100px;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.04em;
        text-decoration: none;
        color: var(--text-hint);
        border: 1px solid #2a2a2a;
        background: transparent;
        transition: all var(--transition);
    }

    .lang-btn:hover { border-color: var(--gold); color: var(--gold); }

    .lang-active {
        background: rgba(200,157,102,0.12);
        border-color: rgba(200,157,102,0.35) !important;
        color: var(--gold) !important;
    }

    /* ── Headings ── */
    .form-title {
        font-size: 1.6rem;
        font-weight: 700;
        color: var(--text-primary);
        letter-spacing: -0.03em;
        margin-bottom: 0.3rem;
    }

    .form-subtitle {
        font-size: 0.875rem;
        color: var(--text-muted);
        margin-bottom: 1.5rem;
    }

    /* ── Alerts ── */
    .alert {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 0.875rem 1rem;
        border-radius: var(--radius-sm);
        font-size: 0.8125rem;
        margin-bottom: 1.25rem;
    }

    .alert svg { width: 18px; height: 18px; flex-shrink: 0; margin-top: 1px; }
    .alert p { margin: 0; line-height: 1.5; }

    .alert-success {
        background: rgba(16,185,129,0.08);
        border: 1px solid rgba(16,185,129,0.2);
        color: #6ee7b7;
    }

    .alert-error {
        background: rgba(239,68,68,0.08);
        border: 1px solid rgba(239,68,68,0.2);
        color: #fca5a5;
    }

    /* ── Form ── */
    .auth-form { display: flex; flex-direction: column; gap: 0.9rem; }

    .field label {
        display: block;
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--text-muted);
        letter-spacing: 0.06em;
        text-transform: uppercase;
        margin-bottom: 0.4rem;
    }

    .input-wrap { position: relative; }

    .field-icon {
        position: absolute;
        left: 14px; top: 50%;
        transform: translateY(-50%);
        width: 17px; height: 17px;
        color: var(--text-hint);
        pointer-events: none;
        transition: color var(--transition);
    }

    .input-wrap input {
        width: 100% !important;
        background: #141414 !important;
        background-color: #141414 !important;
        border: 1px solid #2a2a2a !important;
        border-radius: var(--radius-sm) !important;
        color: #f0f0f0 !important;
        font-size: 0.9375rem !important;
        padding: 0.72rem 3rem 0.72rem 2.75rem !important;
        outline: none !important;
        transition: border-color var(--transition), box-shadow var(--transition);
        -webkit-text-fill-color: #f0f0f0 !important;
        caret-color: var(--gold) !important;
    }

    .input-wrap input:-webkit-autofill,
    .input-wrap input:-webkit-autofill:hover,
    .input-wrap input:-webkit-autofill:focus {
        -webkit-box-shadow: 0 0 0 1000px #141414 inset !important;
        -webkit-text-fill-color: #f0f0f0 !important;
        border-color: #2a2a2a !important;
    }

    .input-wrap input::placeholder { color: #555 !important; }

    .input-wrap input:focus {
        border-color: var(--gold) !important;
        box-shadow: 0 0 0 3px rgba(200,157,102,0.12) !important;
    }

    /* toggle pw */
    .toggle-pw {
        position: absolute;
        right: 12px; top: 50%;
        transform: translateY(-50%);
        background: none; border: none;
        cursor: pointer; padding: 4px;
        color: var(--text-hint);
        line-height: 0;
        transition: color var(--transition);
    }

    .toggle-pw:hover { color: var(--gold); }
    .toggle-pw svg { width: 17px; height: 17px; display: block; }

    /* Password strength */
    .pw-strength {
        height: 3px;
        background: #222;
        border-radius: 10px;
        margin-top: 6px;
        overflow: hidden;
    }

    .pw-bar {
        height: 100%;
        width: 0%;
        border-radius: 10px;
        transition: width 0.3s ease, background 0.3s ease;
    }

    .pw-hint {
        font-size: 0.7rem;
        color: var(--text-hint);
        margin-top: 4px;
    }

    /* Terms */
    .terms-row { margin-top: 0.25rem; }

    .terms-label {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        font-size: 0.8125rem;
        color: var(--text-muted);
        cursor: pointer;
        line-height: 1.5;
    }

    .terms-check {
        width: 15px; height: 15px;
        accent-color: var(--gold);
        cursor: pointer;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .terms-link {
        color: var(--gold);
        text-decoration: none;
        font-weight: 600;
    }

    .terms-link:hover { color: var(--gold-light); }

    /* ── Submit ── */
    .btn-submit {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        padding: 0.875rem;
        background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
        border: none;
        border-radius: var(--radius-sm);
        color: #fff;
        font-size: 0.9375rem;
        font-weight: 700;
        cursor: pointer;
        transition: transform 0.15s ease, box-shadow var(--transition), opacity var(--transition);
        box-shadow: 0 4px 20px rgba(200,157,102,0.25);
        margin-top: 0.25rem;
    }

    .btn-submit svg { width: 18px; height: 18px; transition: transform 0.2s ease; }
    .btn-submit:hover { opacity: 0.92; box-shadow: 0 6px 28px rgba(200,157,102,0.4); }
    .btn-submit:hover svg { transform: translateX(3px); }
    .btn-submit:active { transform: scale(0.985); }

    /* ── Divider ── */
    .divider {
        display: flex; align-items: center;
        gap: 14px;
        margin: 1.25rem 0;
    }

    .divider::before, .divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--border);
    }

    .divider span { font-size: 0.75rem; color: var(--text-hint); }

    /* ── Login link & back ── */
    .register-text {
        text-align: center;
        font-size: 0.875rem;
        color: var(--text-muted);
    }

    .register-text a {
        color: var(--gold);
        font-weight: 600;
        text-decoration: none;
        transition: color var(--transition);
    }

    .register-text a:hover { color: var(--gold-light); }

    .back-home {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        font-size: 0.8125rem;
        color: var(--text-hint);
        text-decoration: none;
        margin-top: 1rem;
        transition: color var(--transition);
    }

    .back-home:hover { color: var(--text-muted); }
    .back-home svg { width: 15px; height: 15px; }

    /* ═══════════════════════
       RIGHT PANEL
    ═══════════════════════ */
    .auth-right {
        display: none;
        width: 50%;
        order: 2;
        position: relative;
        overflow: hidden;
        flex-direction: column;
        justify-content: center;
    }

    .auth-right-bg {
        position: absolute; inset: 0;
        background-size: cover;
        background-position: center;
        transform: scale(1.04);
        transition: transform 8s ease;
    }

    .auth-right:hover .auth-right-bg { transform: scale(1); }

    .auth-right-overlay {
        position: absolute; inset: 0;
        background: linear-gradient(135deg, rgba(10,10,10,0.96) 0%, rgba(10,10,10,0.82) 55%, rgba(10,10,10,0.6) 100%);
        z-index: 1;
    }

    .auth-right-content {
        position: relative;
        z-index: 2;
        padding: 3rem 3.5rem;
        color: #fff;
    }

    .brand-pill {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: rgba(200,157,102,0.12);
        border: 1px solid rgba(200,157,102,0.25);
        border-radius: 100px;
        padding: 5px 14px;
        font-size: 0.7rem;
        font-weight: 600;
        color: var(--gold);
        letter-spacing: 0.1em;
        text-transform: uppercase;
        margin-bottom: 1.5rem;
    }

    .brand-pill::before {
        content: '';
        width: 6px; height: 6px;
        border-radius: 50%;
        background: var(--gold);
        animation: pulse 2s ease infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50%       { opacity: 0.5; transform: scale(0.8); }
    }

    .brand-heading {
        font-size: clamp(1.8rem, 3vw, 2.4rem);
        font-weight: 700;
        color: #fff;
        line-height: 1.25;
        letter-spacing: -0.03em;
        margin-bottom: 0.75rem;
    }

    .brand-desc {
        font-size: 0.875rem;
        color: #777;
        line-height: 1.7;
        margin-bottom: 2rem;
        max-width: 380px;
    }

    /* Steps */
    .steps { display: flex; flex-direction: column; gap: 1rem; margin-bottom: 2.5rem; }

    .step {
        display: flex;
        align-items: flex-start;
        gap: 16px;
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: var(--radius-sm);
        padding: 14px 16px;
        transition: border-color var(--transition);
    }

    .step:hover { border-color: rgba(200,157,102,0.2); }

    .step-num {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--gold);
        opacity: 0.5;
        line-height: 1;
        min-width: 36px;
        flex-shrink: 0;
    }

    .step strong {
        display: block;
        font-size: 0.875rem;
        font-weight: 600;
        color: #ddd;
        margin-bottom: 2px;
    }

    .step span { font-size: 0.775rem; color: #666; line-height: 1.5; }

    /* Stats */
    .stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        border-top: 1px solid rgba(255,255,255,0.07);
        padding-top: 1.75rem;
    }

    .stat { text-align: center; }

    .stat strong {
        display: block;
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--gold);
        letter-spacing: -0.03em;
    }

    .stat span { font-size: 0.7rem; color: #555; text-transform: uppercase; letter-spacing: 0.06em; }

    /* Mobile banner */
    .mobile-banner {
        order: 1;
        background: linear-gradient(135deg, #111 0%, #1a1612 100%);
        border-bottom: 1px solid var(--border);
        padding: 1.75rem 1.5rem;
        text-align: center;
    }

    .mobile-banner h2 {
        font-size: 1.375rem;
        font-weight: 700;
        color: var(--text-primary);
        letter-spacing: -0.02em;
        margin-bottom: 1rem;
    }

    .mobile-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.5rem;
        max-width: 300px;
        margin: 0 auto;
    }

    .mobile-stats div { display: flex; flex-direction: column; align-items: center; }
    .mobile-stats strong { font-size: 1.25rem; font-weight: 700; color: var(--gold); }
    .mobile-stats span  { font-size: 0.7rem; color: var(--text-hint); }

    /* RTL */
    [dir="rtl"] .input-wrap input {
        padding: 0.72rem 2.75rem 0.72rem 3rem !important;
    }

    [dir="rtl"] .field-icon { left: auto; right: 14px; }
    [dir="rtl"] .toggle-pw  { right: auto; left: 12px; }
    [dir="rtl"] .btn-submit svg { transform: scaleX(-1); }
    [dir="rtl"] .back-home svg  { transform: scaleX(-1); }
    [dir="rtl"] .lang-switcher  { flex-direction: row-reverse; }
</style>

<script>
    function togglePassword(inputId, showId, hideId) {
        const input   = document.getElementById(inputId);
        const eyeShow = document.getElementById(showId);
        const eyeHide = document.getElementById(hideId);
        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        eyeShow.style.display = isHidden ? 'none'  : 'block';
        eyeHide.style.display = isHidden ? 'block' : 'none';
    }

    // Password strength meter
    document.getElementById('password').addEventListener('input', function () {
        const val  = this.value;
        const bar  = document.getElementById('pw-bar');
        const hint = document.getElementById('pw-hint');

        let score = 0;
        if (val.length >= 8)            score++;
        if (/[A-Z]/.test(val))          score++;
        if (/[0-9]/.test(val))          score++;
        if (/[^A-Za-z0-9]/.test(val))  score++;

        const levels = [
            { w: '0%',   bg: 'transparent', label: '' },
            { w: '25%',  bg: '#ef4444',     label: '{{ __("messages.pw_weak") }}' },
            { w: '50%',  bg: '#f59e0b',     label: '{{ __("messages.pw_fair") }}' },
            { w: '75%',  bg: '#3b82f6',     label: '{{ __("messages.pw_good") }}' },
            { w: '100%', bg: '#10b981',     label: '{{ __("messages.pw_strong") }}' },
        ];

        bar.style.width      = levels[score].w;
        bar.style.background = levels[score].bg;
        hint.textContent     = levels[score].label;
        hint.style.color     = levels[score].bg;
    });
</script>

@endsection