@extends('layouts.auth')

@section('title', __('messages.forgot_password'))

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

        <div class="card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
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

        <h1 class="card-title">{{ __('messages.forgot_password') }}</h1>
        <p class="card-desc">{{ __('messages.forgot_password_desc') }}</p>

        @if (session('status'))
            <div class="alert alert-success">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <div>@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="auth-form">
            @csrf

            <div class="field">
                <label for="email">{{ __('messages.email') }}</label>
                <div class="input-wrap">
                    <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                    </svg>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus placeholder="your@email.com" autocomplete="email">
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <span>{{ __('messages.send_reset_link') }}</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </button>
        </form>

        <div class="divider"><span>{{ __('messages.or') }}</span></div>

        <p class="register-text">
            {{ __('messages.remembered_password') }}
            <a href="{{ route('login') }}">{{ __('messages.login') }}</a>
        </p>

        <a href="{{ route('home') }}" class="back-home">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            {{ __('messages.back_to_home') }}
        </a>

    </div>
</div>

@include('auth._shared_styles')
@endsection