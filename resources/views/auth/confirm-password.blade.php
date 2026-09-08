@extends('layouts.auth')

@section('title', __('messages.confirm_password'))

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

        {{-- Icon --}}
        <div class="card-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>

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

        {{-- Lang --}}
        <div class="lang-switcher">
            <a href="{{ route('language.switch', 'fr') }}" class="lang-btn {{ app()->getLocale() === 'fr' ? 'lang-active' : '' }}">🇫🇷 FR</a>
            <a href="{{ route('language.switch', 'en') }}" class="lang-btn {{ app()->getLocale() === 'en' ? 'lang-active' : '' }}">🇬🇧 EN</a>
            <a href="{{ route('language.switch', 'ar') }}" class="lang-btn {{ app()->getLocale() === 'ar' ? 'lang-active' : '' }}">🇲🇦 AR</a>
        </div>

        <h1 class="card-title">{{ __('messages.confirm_password') }}</h1>
        <p class="card-desc">{{ __('messages.confirm_password_desc') }}</p>

        @if ($errors->any())
            <div class="alert alert-error">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <div>@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
            </div>
        @endif

        <form method="POST" action="{{ route('password.confirm') }}" class="auth-form">
            @csrf

            <div class="field">
                <label for="password">{{ __('messages.password') }}</label>
                <div class="input-wrap">
                    <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <input type="password" id="password" name="password" required placeholder="••••••••" autocomplete="current-password">
                    <button type="button" class="toggle-pw" onclick="togglePw()" tabindex="-1">
                        <svg id="eye-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg id="eye-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="display:none"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <span>{{ __('messages.confirm') }}</span>
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            </button>
        </form>

        <a href="{{ route('home') }}" class="back-home">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            {{ __('messages.back_to_home') }}
        </a>

    </div>
</div>

@include('auth._shared_styles')
<script>
function togglePw() {
    const i = document.getElementById('password');
    const s = document.getElementById('eye-show');
    const h = document.getElementById('eye-hide');
    const hidden = i.type === 'password';
    i.type = hidden ? 'text' : 'password';
    s.style.display = hidden ? 'none' : 'block';
    h.style.display = hidden ? 'block' : 'none';
}
</script>
@endsection