<header x-data="{
            mobileMenuOpen: false,
            profileOpen: false,
            langOpen: false,
            scrolled: false
        }"
        x-init="
            window.addEventListener('scroll', () => {
                scrolled = window.scrollY > 20
            })
        "
        :class="scrolled
            ? 'bg-[#0a0a0a]/98 border-b border-[#C89D66]/20 shadow-2xl shadow-black/50 py-3'
            : 'bg-[#0a0a0a]/80 border-b border-white/5 py-4'"
        class="fixed w-full z-50 backdrop-blur-xl transition-all duration-500">

    <nav class="container mx-auto px-6">
        <div class="flex items-center justify-between">

            {{-- ── Logo (inchangé) ── --}}
            <a href="{{ route('home') }}" class="flex items-center group flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-[#C89D66] to-[#B8935E] rounded-xl flex items-center justify-center transform group-hover:scale-110 transition-transform duration-300 shadow-lg shadow-[#C89D66]/30">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                            <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/>
                        </svg>
                    </div>
                    <div>
                        <span class="text-2xl font-bold text-[#C89D66] tracking-tight">CarRental</span>
                        <span class="text-sm text-gray-400 block -mt-1">Morocco</span>
                    </div>
                </div>
            </a>

            {{-- ── Desktop Menu ── --}}
            <div class="hidden md:flex items-center gap-1">

                <a href="{{ route('home') }}"
                   class="nav-link {{ request()->routeIs('home') ? 'nav-link--active' : '' }}">
                    {{ __('messages.home') }}
                </a>

                <a href="{{ route('cars.index') }}"
                   class="nav-link {{ request()->routeIs('cars.*') ? 'nav-link--active' : '' }}">
                    {{ __('messages.cars') }}
                </a>

                <a href="{{ route('about') }}"
                   class="nav-link {{ request()->routeIs('about*') ? 'nav-link--active' : '' }}">
                    {{ __('messages.about') }}
                </a>

                <a href="{{ route('contact') }}"
                   class="nav-link {{ request()->routeIs('contact*') ? 'nav-link--active' : '' }}">
                    {{ __('messages.contact') }}
                </a>

            </div>

            {{-- ── Right side ── --}}
            <div class="hidden md:flex items-center gap-3">

                {{-- Language switcher --}}
                <div class="relative" x-data>
                    <button @click="langOpen = !langOpen"
                            type="button"
                            class="lang-toggle">
                        @if(app()->getLocale() == 'en')
                            <span>🇬🇧</span><span class="hidden lg:inline text-xs font-semibold">EN</span>
                        @elseif(app()->getLocale() == 'fr')
                            <span>🇫🇷</span><span class="hidden lg:inline text-xs font-semibold">FR</span>
                        @else
                            <span>🇲🇦</span><span class="hidden lg:inline text-xs font-semibold">AR</span>
                        @endif
                        <svg class="w-3 h-3 text-gray-500 transition-transform duration-200" :class="langOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="langOpen"
                         @click.away="langOpen = false"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="lang-dropdown"
                         style="display:none">

                        <a href="{{ route('language.switch', 'en') }}"
                           class="lang-item {{ app()->getLocale() == 'en' ? 'lang-item--active' : '' }}">
                            <span class="text-base">🇬🇧</span>
                            <span>English</span>
                            @if(app()->getLocale() == 'en')
                                <svg class="w-3.5 h-3.5 ml-auto text-[#C89D66]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            @endif
                        </a>

                        <a href="{{ route('language.switch', 'fr') }}"
                           class="lang-item {{ app()->getLocale() == 'fr' ? 'lang-item--active' : '' }}">
                            <span class="text-base">🇫🇷</span>
                            <span>Français</span>
                            @if(app()->getLocale() == 'fr')
                                <svg class="w-3.5 h-3.5 ml-auto text-[#C89D66]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            @endif
                        </a>

                        <a href="{{ route('language.switch', 'ar') }}"
                           class="lang-item {{ app()->getLocale() == 'ar' ? 'lang-item--active' : '' }}">
                            <span class="text-base">🇲🇦</span>
                            <span>العربية</span>
                            @if(app()->getLocale() == 'ar')
                                <svg class="w-3.5 h-3.5 ml-auto text-[#C89D66]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            @endif
                        </a>
                    </div>
                </div>

                {{-- Divider --}}
                <div class="w-px h-5 bg-white/10"></div>

                @guest
                    <a href="{{ route('login') }}"
                       class="px-4 py-2 text-gray-300 hover:text-[#C89D66] text-sm font-medium transition-colors duration-200">
                        {{ __('messages.login') }}
                    </a>
                    <a href="{{ route('register') }}"
                       class="register-btn">
                        {{ __('messages.register') }}
                    </a>
                @endguest

                @auth
                    {{-- Profile dropdown --}}
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open"
                                class="profile-btn group">
                            @if(auth()->user()->profile_photo_path)
                                <img src="{{ asset('storage/'.auth()->user()->profile_photo_path) }}"
                                     class="w-8 h-8 rounded-full object-cover border border-[#C89D66]/30 group-hover:border-[#C89D66] transition-all">
                            @else
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#C89D66] to-[#B8935E] flex items-center justify-center text-white text-sm font-bold shadow-md shadow-[#C89D66]/20">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </div>
                            @endif
                            <div class="hidden lg:block text-left">
                                <p class="text-white text-sm font-semibold leading-tight">{{ auth()->user()->name }}</p>
                                <p class="text-gray-500 text-xs">{{ __('messages.profile') }}</p>
                            </div>
                            <svg class="w-3.5 h-3.5 text-gray-500 transition-transform duration-200"
                                 :class="open ? 'rotate-180' : ''"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="open"
                             @click.outside="open = false"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="profile-dropdown"
                             style="display:none">

                            {{-- User header --}}
                            <div class="px-5 py-4 border-b border-white/5">
                                <div class="flex items-center gap-3">
                                    @if(auth()->user()->profile_photo_path)
                                        <img src="{{ asset('storage/'.auth()->user()->profile_photo_path) }}"
                                             class="w-10 h-10 rounded-full object-cover border border-[#C89D66]/20">
                                    @else
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-[#C89D66] to-[#B8935E] flex items-center justify-center text-white font-bold">
                                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <p class="text-white text-sm font-semibold">{{ auth()->user()->name }}</p>
                                        <p class="text-gray-500 text-xs truncate max-w-[160px]">{{ auth()->user()->email }}</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Menu items --}}
                            <div class="py-1.5">
                                <a href="{{ route('profile.edit') }}" class="dropdown-item">
                                    <div class="dropdown-item-icon">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-white">{{ __('messages.my_account') }}</p>
                                        <p class="text-xs text-gray-500">{{ __('messages.manage_profile') }}</p>
                                    </div>
                                </a>

                                <a href="{{ route('my_booking.index') }}" class="dropdown-item">
                                    <div class="dropdown-item-icon">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-white">{{ __('messages.my_bookings') }}</p>
                                        <p class="text-xs text-gray-500">{{ __('messages.view_rentals') }}</p>
                                    </div>
                                </a>

                                <a href="#" class="dropdown-item">
                                    <div class="dropdown-item-icon">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-white">{{ __('messages.settings') }}</p>
                                        <p class="text-xs text-gray-500">{{ __('messages.preferences') }}</p>
                                    </div>
                                </a>
                            </div>

                            {{-- Logout --}}
                            <div class="border-t border-white/5 py-1.5">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item dropdown-item--danger w-full">
                                        <div class="dropdown-item-icon dropdown-item-icon--danger">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                        </div>
                                        <div class="text-left">
                                            <p class="text-sm font-medium">{{ __('messages.logout') }}</p>
                                            <p class="text-xs opacity-60">{{ __('messages.logout_account') }}</p>
                                        </div>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endauth
            </div>

            {{-- ── Mobile burger ── --}}
            <button @click="mobileMenuOpen = !mobileMenuOpen"
                    class="md:hidden w-9 h-9 flex items-center justify-center rounded-lg border border-white/10 text-gray-300 hover:border-[#C89D66]/40 hover:text-[#C89D66] transition-all duration-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    <path x-show="mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- ── Mobile menu ── --}}
        <div x-show="mobileMenuOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="md:hidden mt-3 pb-3 border-t border-white/5"
             style="display:none">

            <div class="pt-3 space-y-0.5">
                <a href="{{ route('home') }}"       class="mobile-link">{{ __('messages.home') }}</a>
                <a href="{{ route('cars.index') }}" class="mobile-link">{{ __('messages.cars') }}</a>
                <a href="{{ route('about') }}"      class="mobile-link">{{ __('messages.about') }}</a>
                <a href="#"                         class="mobile-link">{{ __('messages.contact') }}</a>
            </div>

            {{-- Mobile lang --}}
            <div class="px-3 pt-4 pb-1">
                <p class="text-xs text-gray-600 uppercase tracking-widest mb-2">{{ __('messages.language') }}</p>
                <div class="flex gap-2">
                    <a href="{{ route('language.switch', 'en') }}"
                       class="mobile-lang {{ app()->getLocale() == 'en' ? 'mobile-lang--on' : '' }}">🇬🇧 EN</a>
                    <a href="{{ route('language.switch', 'fr') }}"
                       class="mobile-lang {{ app()->getLocale() == 'fr' ? 'mobile-lang--on' : '' }}">🇫🇷 FR</a>
                    <a href="{{ route('language.switch', 'ar') }}"
                       class="mobile-lang {{ app()->getLocale() == 'ar' ? 'mobile-lang--on' : '' }}">🇲🇦 AR</a>
                </div>
            </div>

            {{-- Mobile auth --}}
            @guest
                <div class="px-3 pt-3 flex flex-col gap-2">
                    <a href="{{ route('login') }}"
                       class="text-center py-2.5 text-gray-300 border border-white/10 rounded-xl text-sm font-medium hover:border-[#C89D66]/40 transition">
                        {{ __('messages.login') }}
                    </a>
                    <a href="{{ route('register') }}"
                       class="text-center py-2.5 bg-gradient-to-r from-[#C89D66] to-[#B8935E] text-white rounded-xl text-sm font-bold">
                        {{ __('messages.register') }}
                    </a>
                </div>
            @endguest

            @auth
                <div class="px-3 pt-3 space-y-0.5">
                    <a href="{{ route('profile.edit') }}"    class="mobile-link">{{ __('messages.my_account') }}</a>
                    <a href="{{ route('my_booking.index') }}" class="mobile-link">{{ __('messages.my_bookings') }}</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="mobile-link text-red-400 hover:text-red-300 w-full text-left">{{ __('messages.logout') }}</button>
                    </form>
                </div>
            @endauth
        </div>
    </nav>
</header>

{{-- Spacer --}}
<div class="h-[72px]"></div>

{{-- ── Navbar styles ── --}}
<style>
/* Nav links */
.nav-link {
    position: relative;
    padding: 8px 14px;
    font-size: 0.875rem;
    font-weight: 500;
    color: #9ca3af;
    border-radius: 8px;
    text-decoration: none;
    transition: color 0.2s, background 0.2s;
}

.nav-link::after {
    content: '';
    position: absolute;
    bottom: 4px;
    left: 50%; right: 50%;
    height: 1.5px;
    background: #C89D66;
    transition: left 0.25s ease, right 0.25s ease;
    border-radius: 2px;
}

.nav-link:hover { color: #fff; }
.nav-link:hover::after { left: 14px; right: 14px; }

.nav-link--active {
    color: #C89D66 !important;
    background: rgba(200,157,102,0.08);
}

.nav-link--active::after { left: 14px; right: 14px; }

/* Language toggle */
.lang-toggle {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 10px;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 8px;
    color: #e5e7eb;
    cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
    font-size: 0.875rem;
}
.lang-toggle:hover { border-color: rgba(200,157,102,0.35); background: rgba(200,157,102,0.06); }

/* Language dropdown */
.lang-dropdown {
    position: absolute;
    right: 0; top: calc(100% + 8px);
    width: 160px;
    background: #0f0f0f;
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(0,0,0,0.5);
}

.lang-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    font-size: 0.82rem;
    font-weight: 500;
    color: #9ca3af;
    text-decoration: none;
    transition: background 0.15s, color 0.15s;
}
.lang-item:hover { background: rgba(255,255,255,0.05); color: #fff; }
.lang-item--active { background: rgba(200,157,102,0.1); color: #C89D66; }

/* Profile button */
.profile-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 10px;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 10px;
    cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
}
.profile-btn:hover { border-color: rgba(200,157,102,0.3); background: rgba(200,157,102,0.05); }

/* Profile dropdown */
.profile-dropdown {
    position: absolute;
    right: 0; top: calc(100% + 8px);
    width: 260px;
    background: #0f0f0f;
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 24px 48px rgba(0,0,0,0.6);
}

/* Dropdown items */
.dropdown-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 16px;
    text-decoration: none;
    transition: background 0.15s;
    cursor: pointer;
    border: none;
    background: transparent;
}
.dropdown-item:hover { background: rgba(255,255,255,0.04); }

.dropdown-item-icon {
    width: 32px; height: 32px;
    border-radius: 8px;
    background: rgba(255,255,255,0.05);
    display: flex; align-items: center; justify-content: center;
    color: #6b7280;
    flex-shrink: 0;
    transition: background 0.15s, color 0.15s;
}
.dropdown-item:hover .dropdown-item-icon { background: rgba(200,157,102,0.12); color: #C89D66; }

.dropdown-item--danger { color: #f87171; }
.dropdown-item--danger:hover { background: rgba(248,113,113,0.06); }
.dropdown-item-icon--danger { color: #f87171 !important; }
.dropdown-item--danger:hover .dropdown-item-icon--danger { background: rgba(248,113,113,0.12) !important; }

/* Register button */
.register-btn {
    padding: 8px 18px;
    background: linear-gradient(135deg, #C89D66, #B8935E);
    color: #fff;
    font-size: 0.875rem;
    font-weight: 700;
    border-radius: 9px;
    text-decoration: none;
    box-shadow: 0 4px 14px rgba(200,157,102,0.25);
    transition: opacity 0.2s, box-shadow 0.2s, transform 0.15s;
}
.register-btn:hover { opacity: 0.9; box-shadow: 0 6px 20px rgba(200,157,102,0.4); transform: scale(1.02); }

/* Mobile links */
.mobile-link {
    display: block;
    padding: 10px 12px;
    font-size: 0.875rem;
    font-weight: 500;
    color: #9ca3af;
    border-radius: 8px;
    text-decoration: none;
    transition: background 0.15s, color 0.15s;
}
.mobile-link:hover { background: rgba(255,255,255,0.04); color: #fff; }

/* Mobile lang */
.mobile-lang {
    flex: 1;
    text-align: center;
    padding: 8px 4px;
    font-size: 0.78rem;
    font-weight: 600;
    border-radius: 8px;
    text-decoration: none;
    color: #6b7280;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.06);
    transition: all 0.15s;
}
.mobile-lang:hover { border-color: rgba(200,157,102,0.3); color: #C89D66; }
.mobile-lang--on { background: rgba(200,157,102,0.12); border-color: rgba(200,157,102,0.3); color: #C89D66; }
</style>