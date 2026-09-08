<footer class="ft">

    @if(collect($footerContact)->filter()->isNotEmpty())
        <div class="ft-top">
            @if($footerContact['phone'])
                <div class="ft-contact">
                    <div class="ft-contact-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </div>
                    <div>
                        <span>{{ __('messages.footer_call') }}</span>
                        <strong>{{ $footerContact['phone'] }}</strong>
                    </div>
                </div>
            @endif

            @if($footerContact['email'])
                <div class="ft-contact">
                    <div class="ft-contact-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <span>{{ __('messages.footer_write') }}</span>
                        <strong>{{ $footerContact['email'] }}</strong>
                    </div>
                </div>
            @endif

            @if($footerContact['address'])
                <div class="ft-contact">
                    <div class="ft-contact-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <span>{{ __('messages.footer_address_label') }}</span>
                        <strong>{{ $footerContact['address'] }}</strong>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <div class="ft-body">
        <div class="ft-brand">
            <a href="{{ route('home') }}" class="ft-logo">
                <div class="ft-logo-icon">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                        <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/>
                    </svg>
                </div>
                <div>
                    <span class="ft-logo-name">CarRental</span>
                    <span class="ft-logo-sub">Morocco</span>
                </div>
            </a>

            <p class="ft-brand-desc">{{ __('messages.footer_tagline') }}</p>

            @if(! empty($footerSocialLinks))
                <div class="ft-socials" aria-label="{{ __('messages.social_media') }}">
                    @foreach($footerSocialLinks as $socialLink)
                        <a href="{{ $socialLink['url'] }}"
                           class="ft-social"
                           target="_blank"
                           rel="noopener noreferrer"
                           aria-label="{{ $socialLink['label'] }}">
                            <i class="{{ $socialLink['icon'] }}" aria-hidden="true"></i>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="ft-col">
            <h3 class="ft-col-title">{{ __('messages.quick_links') }}</h3>
            <ul class="ft-links">
                <li><a href="{{ route('home') }}">{{ __('messages.home') }}</a></li>
                <li><a href="{{ route('cars.index') }}">{{ __('messages.cars') }}</a></li>
                <li><a href="{{ route('about') }}">{{ __('messages.about') }}</a></li>
                <li><a href="{{ route('contact') }}">{{ __('messages.contact') }}</a></li>
            </ul>
        </div>

        <div class="ft-col">
            <h3 class="ft-col-title">{{ __('messages.customer_service') }}</h3>
            <ul class="ft-links">
                @guest
                    <li><a href="{{ route('login') }}">{{ __('messages.login') }}</a></li>
                    <li><a href="{{ route('register') }}">{{ __('messages.register') }}</a></li>
                @endguest
                @auth
                    <li><a href="{{ route('my_booking.index') }}">{{ __('messages.my_bookings') }}</a></li>
                    <li><a href="{{ route('profile.edit') }}">{{ __('messages.my_account') }}</a></li>
                    <li><a href="{{ route('tickets.index') }}">{{ __('messages.support_title') }}</a></li>
                @endauth
            </ul>
        </div>

        <div class="ft-col">
            <h3 class="ft-col-title">{{ __('messages.legal') }}</h3>
            <ul class="ft-links">
                <li><a href="{{ route('legal.privacy') }}">{{ __('messages.privacy_policy') }}</a></li>
                <li><a href="{{ route('legal.terms') }}">{{ __('messages.terms_conditions') }}</a></li>
                <li><a href="{{ route('legal.notice') }}">{{ __('messages.legal_notice') }}</a></li>
            </ul>
        </div>
    </div>

    <div class="ft-bottom">
        <p>&copy; {{ date('Y') }} CarRental Morocco. {{ __('messages.all_rights_reserved') }}</p>
    </div>

</footer>

<style>
.ft {
    background: #080808;
    border-top: 1px solid rgba(255,255,255,0.06);
    font-family: inherit;
}

.ft-top {
    display: flex;
    align-items: stretch;
    justify-content: center;
    flex-wrap: wrap;
    gap: 0;
    background: #0f0f0f;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    padding: 0;
}

.ft-contact {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 1.5rem 3rem;
    flex: 1;
    min-width: 220px;
    border-right: 1px solid rgba(255,255,255,0.06);
    transition: background 0.2s;
}

.ft-contact:last-child { border-right: 0; }
.ft-contact:hover { background: rgba(200,157,102,0.04); }

.ft-contact-icon {
    width: 44px; height: 44px;
    border-radius: 50%;
    background: rgba(200,157,102,0.1);
    border: 1px solid rgba(200,157,102,0.2);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    color: #C89D66;
    transition: background 0.2s;
}

.ft-contact:hover .ft-contact-icon { background: rgba(200,157,102,0.18); }
.ft-contact-icon svg { width: 18px; height: 18px; }
.ft-contact span { display: block; font-size: 0.7rem; color: #555; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 3px; }
.ft-contact strong { display: block; font-size: 0.9rem; font-weight: 600; color: #e0dcd4; overflow-wrap: anywhere; }

@media (max-width: 768px) {
    .ft-contact {
        border-right: 0;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
}

.ft-body {
    max-width: 1200px;
    margin: 0 auto;
    padding: 4rem 2rem 3rem;
    display: grid;
    grid-template-columns: 1.6fr 1fr 1fr 1fr;
    gap: 3.5rem;
}

@media (max-width: 1024px) { .ft-body { grid-template-columns: 1fr 1fr; gap: 2.5rem; } }
@media (max-width: 600px) { .ft-body { grid-template-columns: 1fr; gap: 2rem; padding: 3rem 1.5rem 2rem; } }

.ft-logo {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    margin-bottom: 1.25rem;
}

.ft-logo-icon {
    width: 38px; height: 38px;
    background: linear-gradient(135deg, #C89D66, #B8935E);
    border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 4px 14px rgba(200,157,102,0.2);
    flex-shrink: 0;
}

.ft-logo-icon svg { width: 20px; height: 20px; color: #fff; }
.ft-logo-name { display: block; font-size: 1.1rem; font-weight: 700; color: #C89D66; letter-spacing: -0.02em; }
.ft-logo-sub { display: block; font-size: 0.65rem; color: #555; letter-spacing: 0.08em; text-transform: uppercase; }

.ft-brand-desc {
    font-size: 0.875rem;
    color: #666;
    line-height: 1.8;
    margin-bottom: 1.5rem;
    max-width: 280px;
}

.ft-socials { display: flex; gap: 8px; flex-wrap: wrap; }

.ft-social {
    width: 36px; height: 36px;
    border-radius: 8px;
    border: 1px solid rgba(255,255,255,0.08);
    background: transparent;
    display: flex; align-items: center; justify-content: center;
    color: #666;
    text-decoration: none;
    transition: border-color 0.2s, background 0.2s, color 0.2s;
}

.ft-social i { font-size: 0.95rem; }
.ft-social:hover { border-color: rgba(200,157,102,0.4); background: rgba(200,157,102,0.08); color: #C89D66; }

.ft-col-title {
    font-size: 0.72rem;
    font-weight: 700;
    color: #f0ece4;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    margin-bottom: 1.25rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid rgba(255,255,255,0.06);
}

.ft-links { list-style: none; display: flex; flex-direction: column; gap: 8px; }

.ft-links a {
    font-size: 0.875rem;
    color: #666;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: color 0.2s;
}

.ft-links a::before {
    content: '';
    width: 4px; height: 4px;
    border-radius: 50%;
    background: #333;
    flex-shrink: 0;
    transition: background 0.2s;
}

.ft-links a:hover { color: #C89D66; }
.ft-links a:hover::before { background: #C89D66; }

.ft-bottom {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1.25rem 2rem;
    border-top: 1px solid rgba(255,255,255,0.05);
}

.ft-bottom p { font-size: 0.78rem; color: #444; }

[dir="rtl"] .ft-contact { border-right: 0; border-left: 1px solid rgba(255,255,255,0.06); }
[dir="rtl"] .ft-contact:last-child { border-left: 0; }
[dir="rtl"] .ft-links a { flex-direction: row-reverse; }
</style>
