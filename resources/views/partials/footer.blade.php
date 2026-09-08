<footer class="ft">

    {{-- ══ Top contact bar ══ --}}
    <div class="ft-top">
        <div class="ft-contact">
            <div class="ft-contact-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
            </div>
            <div>
                <span>{{ __('messages.footer_call') }}</span>
                <strong>+212 600-123456</strong>
            </div>
        </div>

        <div class="ft-contact-divider"></div>

        <div class="ft-contact">
            <div class="ft-contact-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <span>{{ __('messages.footer_write') }}</span>
                <strong>info@carrentalmorocco.com</strong>
            </div>
        </div>

        <div class="ft-contact-divider"></div>

        <div class="ft-contact">
            <div class="ft-contact-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div>
                <span>{{ __('messages.footer_address_label') }}</span>
                <strong>{{ __('messages.footer_address') }}</strong>
            </div>
        </div>
    </div>

    {{-- ══ Main body ══ --}}
    <div class="ft-body">

        {{-- Brand col --}}
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

            <div class="ft-socials">
                <a href="#" class="ft-social" aria-label="WhatsApp">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                </a>
                <a href="#" class="ft-social" aria-label="Facebook">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                </a>
                <a href="#" class="ft-social" aria-label="YouTube">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                    </svg>
                </a>
                <a href="#" class="ft-social" aria-label="Instagram">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                    </svg>
                </a>
            </div>
        </div>

        {{-- Quick links --}}
        <div class="ft-col">
            <h3 class="ft-col-title">{{ __('messages.quick_links') }}</h3>
            <ul class="ft-links">
                <li><a href="{{ route('about') }}">{{ __('messages.about') }}</a></li>
                <li><a href="{{ route('cars.index') }}">{{ __('messages.cars') }}</a></li>
                <li><a href="#">{{ __('messages.contact') }}</a></li>
                <li><a href="#">{{ __('messages.faq') }}</a></li>
                <li><a href="#">{{ __('messages.terms_conditions') }}</a></li>
            </ul>
        </div>

        {{-- Customer service --}}
        <div class="ft-col">
            <h3 class="ft-col-title">{{ __('messages.customer_service') }}</h3>
            <ul class="ft-links">
                <li><a href="#">{{ __('messages.privacy_policy') }}</a></li>
                @guest
                    <li><a href="{{ route('login') }}">{{ __('messages.login') }}</a></li>
                    <li><a href="{{ route('register') }}">{{ __('messages.register') }}</a></li>
                @endguest
                @auth
                    <li><a href="{{ route('my_booking.index') }}">{{ __('messages.my_bookings') }}</a></li>
                    <li><a href="{{ route('profile.edit') }}">{{ __('messages.my_account') }}</a></li>
                @endauth
            </ul>
        </div>

        {{-- Newsletter --}}
        <div class="ft-col">
            <h3 class="ft-col-title">{{ __('messages.footer_subscribe') }}</h3>
            <p class="ft-col-desc">{{ __('messages.footer_subscribe_desc') }}</p>

            <form class="ft-subscribe" onsubmit="return false">
                <div class="ft-input-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <input type="email" placeholder="{{ __('messages.footer_email_placeholder') }}">
                </div>
                <button type="submit" class="ft-sub-btn">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                    {{ __('messages.footer_subscribe_btn') }}
                </button>
            </form>
        </div>

    </div>

    {{-- ══ Bottom bar ══ --}}
    <div class="ft-bottom">
        <p>© {{ date('Y') }} CarRental Morocco. {{ __('messages.all_rights_reserved') }}</p>
        <div class="ft-bottom-links">
            <a href="#">{{ __('messages.privacy_policy') }}</a>
            <span>·</span>
            <a href="#">{{ __('messages.terms_conditions') }}</a>
        </div>
    </div>

</footer>

<style>
.ft {
    background: #080808;
    border-top: 1px solid rgba(255,255,255,0.06);
    font-family: inherit;
}

/* ── Top contact bar ── */
.ft-top {
    display: flex;
    align-items: center;
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
    transition: background 0.2s;
}

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

.ft-contact span  { display: block; font-size: 0.7rem; color: #555; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 3px; }
.ft-contact strong { display: block; font-size: 0.9rem; font-weight: 600; color: #e0dcd4; }

.ft-contact-divider {
    width: 1px;
    height: 50px;
    background: rgba(255,255,255,0.06);
    flex-shrink: 0;
}

@media (max-width: 768px) {
    .ft-contact-divider { display: none; }
    .ft-contact { border-bottom: 1px solid rgba(255,255,255,0.05); }
}

/* ── Main body ── */
.ft-body {
    max-width: 1200px;
    margin: 0 auto;
    padding: 4rem 2rem 3rem;
    display: grid;
    grid-template-columns: 1.6fr 1fr 1fr 1.4fr;
    gap: 3.5rem;
}

@media (max-width: 1024px) { .ft-body { grid-template-columns: 1fr 1fr; gap: 2.5rem; } }
@media (max-width: 600px)  { .ft-body { grid-template-columns: 1fr; gap: 2rem; padding: 3rem 1.5rem 2rem; } }

/* Brand */
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
.ft-logo-sub  { display: block; font-size: 0.65rem; color: #555; letter-spacing: 0.08em; text-transform: uppercase; }

.ft-brand-desc {
    font-size: 0.875rem;
    color: #666;
    line-height: 1.8;
    margin-bottom: 1.5rem;
    max-width: 280px;
}

/* Socials */
.ft-socials { display: flex; gap: 8px; }

.ft-social {
    width: 36px; height: 36px;
    border-radius: 8px;
    border: 1px solid rgba(255,255,255,0.08);
    background: transparent;
    display: flex; align-items: center; justify-content: center;
    color: #555;
    text-decoration: none;
    transition: border-color 0.2s, background 0.2s, color 0.2s;
}

.ft-social svg { width: 15px; height: 15px; }
.ft-social:hover { border-color: rgba(200,157,102,0.4); background: rgba(200,157,102,0.08); color: #C89D66; }

/* Columns */
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

.ft-col-desc {
    font-size: 0.82rem;
    color: #555;
    line-height: 1.7;
    margin-bottom: 1rem;
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

/* Subscribe form */
.ft-subscribe { display: flex; flex-direction: column; gap: 8px; }

.ft-input-wrap { position: relative; }

.ft-input-wrap svg {
    position: absolute;
    left: 12px; top: 50%;
    transform: translateY(-50%);
    width: 15px; height: 15px;
    color: #444;
    pointer-events: none;
}

.ft-input-wrap input {
    width: 100% !important;
    background: #111 !important;
    background-color: #111 !important;
    border: 1px solid rgba(255,255,255,0.08) !important;
    border-radius: 8px !important;
    color: #e0dcd4 !important;
    font-size: 0.82rem !important;
    padding: 10px 12px 10px 34px !important;
    outline: none !important;
    -webkit-text-fill-color: #e0dcd4 !important;
    transition: border-color 0.2s;
}

.ft-input-wrap input:-webkit-autofill {
    -webkit-box-shadow: 0 0 0 1000px #111 inset !important;
    -webkit-text-fill-color: #e0dcd4 !important;
}

.ft-input-wrap input::placeholder { color: #444 !important; }
.ft-input-wrap input:focus { border-color: rgba(200,157,102,0.4) !important; }

.ft-sub-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    width: 100%;
    padding: 10px;
    background: linear-gradient(135deg, #C89D66, #B8935E);
    border: none;
    border-radius: 8px;
    color: #fff;
    font-size: 0.8rem;
    font-weight: 700;
    cursor: pointer;
    transition: opacity 0.2s, box-shadow 0.2s;
    box-shadow: 0 4px 14px rgba(200,157,102,0.2);
}

.ft-sub-btn svg { width: 14px; height: 14px; transition: transform 0.2s; }
.ft-sub-btn:hover { opacity: 0.9; box-shadow: 0 6px 20px rgba(200,157,102,0.35); }
.ft-sub-btn:hover svg { transform: translateX(3px); }

/* Bottom bar */
.ft-bottom {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1.25rem 2rem;
    border-top: 1px solid rgba(255,255,255,0.05);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.ft-bottom p { font-size: 0.78rem; color: #444; }

.ft-bottom-links {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.78rem;
    color: #444;
}

.ft-bottom-links a { color: #555; text-decoration: none; transition: color 0.2s; }
.ft-bottom-links a:hover { color: #C89D66; }

/* RTL */
[dir="rtl"] .ft-input-wrap svg { left: auto; right: 12px; }
[dir="rtl"] .ft-input-wrap input { padding: 10px 34px 10px 12px !important; }
[dir="rtl"] .ft-sub-btn:hover svg { transform: scaleX(-1) translateX(3px); }
[dir="rtl"] .ft-links a { flex-direction: row-reverse; }
</style>