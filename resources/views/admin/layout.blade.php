<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Admin') | CarRental</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
        --sb-w:    240px;
        --bg:      #07090f;
        --bg-2:    #0d1117;
        --bg-3:    #111827;
        --border:  rgba(255,255,255,0.06);
        --text:    #f1f5f9;
        --muted:   #64748b;
        --hint:    #374151;
        --gold:    #C89D66;
        --gold-d:  #B8935E;
        --active:  rgba(200,157,102,0.1);
    }

    html, body {
        height: 100%;
        background: #07090f !important;
        font-family: 'DM Sans', system-ui, -apple-system, sans-serif;
        color: var(--text);
        overflow: hidden;
    }

    /* Kill any Tailwind bg classes that might override */
    .bg-gray-100, .bg-white, .bg-gray-50, .bg-gray-200 {
        background-color: transparent !important;
    }

    /* ── SHELL ── */
    .adm-shell {
        display: flex;
        height: 100vh;
        width: 100vw;
        overflow: hidden;
    }

    /* ── SIDEBAR ── */
    .adm-sidebar {
        width: var(--sb-w);
        flex-shrink: 0;
        background: var(--bg-2);
        border-right: 1px solid var(--border);
        display: flex;
        flex-direction: column;
        overflow-y: auto;
        overflow-x: hidden;
        scrollbar-width: none;
    }
    .adm-sidebar::-webkit-scrollbar { display: none; }

    /* Logo */
    .adm-logo {
        padding: 1.25rem 1.25rem 1rem;
        border-bottom: 1px solid var(--border);
        flex-shrink: 0;
    }
    .adm-logo-inner {
        display: flex; align-items: center; gap: 10px;
        text-decoration: none;
    }
    .adm-logo-icon {
        width: 32px; height: 32px;
        background: linear-gradient(135deg, var(--gold), var(--gold-d));
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(200,157,102,0.25);
    }
    .adm-logo-icon svg { width: 16px; height: 16px; color: #fff; }
    .adm-logo-name { font-size: 0.9rem; font-weight: 800; color: var(--text); letter-spacing: -0.02em; }
    .adm-logo-sub  { font-size: 0.58rem; color: var(--muted); letter-spacing: 0.08em; text-transform: uppercase; }

    /* Nav group */
    .adm-nav { flex: 1; padding: 0.75rem 0.75rem; }

    .adm-nav-group { margin-bottom: 1.25rem; }
    .adm-nav-group-label {
        font-size: 0.58rem; font-weight: 700;
        color: var(--hint); letter-spacing: 0.15em;
        text-transform: uppercase;
        padding: 0 0.5rem;
        margin-bottom: 0.4rem;
    }

    /* Nav item */
    .adm-nav-item {
        display: flex; align-items: center; gap: 10px;
        padding: 8px 10px;
        border-radius: 8px;
        color: var(--muted);
        text-decoration: none;
        font-size: 0.82rem; font-weight: 500;
        transition: background 0.15s, color 0.15s;
        margin-bottom: 1px;
        position: relative;
    }
    .adm-nav-item svg { width: 16px; height: 16px; flex-shrink: 0; transition: color 0.15s; }
    .adm-nav-item:hover { background: rgba(255,255,255,0.04); color: var(--text); }
    .adm-nav-item:hover svg { color: var(--gold); }

    .adm-nav-item.active {
        background: var(--active);
        color: var(--gold);
    }
    .adm-nav-item.active svg { color: var(--gold); }
    .adm-nav-item.active::before {
        content: '';
        position: absolute; left: 0; top: 20%; bottom: 20%;
        width: 2px; border-radius: 2px;
        background: var(--gold);
    }

    /* Badge on nav item */
    .adm-nav-badge {
        margin-left: auto;
        min-width: 20px; height: 20px;
        display: inline-flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        font-size: 0.65rem; font-weight: 800;
        line-height: 1;
        padding: 0 6px; border-radius: 999px;
        background: #dc2626;
        color: #fff;
        box-shadow: 0 0 0 1px rgba(255,255,255,0.08);
    }

    /* Sidebar footer */
    .adm-sidebar-footer {
        padding: 0.75rem;
        border-top: 1px solid var(--border);
        flex-shrink: 0;
    }
    .adm-logout {
        display: flex; align-items: center; gap: 10px;
        width: 100%; padding: 8px 10px;
        background: transparent; border: none;
        border-radius: 8px;
        color: var(--muted); font-size: 0.82rem;
        cursor: pointer; text-decoration: none;
        transition: background 0.15s, color 0.15s;
    }
    .adm-logout svg { width: 16px; height: 16px; }
    .adm-logout:hover { background: rgba(239,68,68,0.08); color: #f87171; }

    /* ── MAIN ── */
    .adm-main {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
        background: #07090f !important;
        min-width: 0;
    }
    .adm-main::-webkit-scrollbar { width: 4px; }
    .adm-main::-webkit-scrollbar-track { background: transparent; }
    .adm-main::-webkit-scrollbar-thumb { background: var(--hint); border-radius: 2px; }
    </style>
</head>
<body>

<div class="adm-shell">
    @php
        $adminAttentionCounts = $adminAttentionCounts ?? [];
    @endphp

    {{-- ══ SIDEBAR ══ --}}
    <aside class="adm-sidebar">

        {{-- Logo --}}
        <div class="adm-logo">
            <a href="{{ route('admin.dashboard') }}" class="adm-logo-inner">
                <div class="adm-logo-icon">
                   <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                       <path stroke-linecap="round" stroke-linejoin="round" d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                       <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/>
                   </svg>
                </div>
                <div>
                    <div class="adm-logo-name">CarRental</div>
                    <div class="adm-logo-sub">Admin Panel</div>
                </div>
            </a>
        </div>

        {{-- Nav --}}
        <nav class="adm-nav">

            {{-- Main --}}
            <div class="adm-nav-group">
                <div class="adm-nav-group-label">Main</div>

                <a href="{{ route('admin.dashboard') }}"
                   class="adm-nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                   <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                       <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                   </svg>
                    Dashboard
                </a>

                <a href="{{ route('admin.bookings.index') }}"
                   class="adm-nav-item {{ request()->routeIs('admin.bookings.*') ? 'active' : '' }}">
                   <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                       <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                   </svg>
                    Bookings
                    <x-admin.attention-badge
                        :count="$adminAttentionCounts['bookings'] ?? 0"
                        key-name="bookings"
                        label="Bookings"
                    />
                </a>

                <a href="{{ route('admin.cars.index') }}"
                   class="adm-nav-item {{ request()->routeIs('admin.cars.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                       <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                   </svg>
                    Cars
                </a>

                <a href="{{ route('admin.users.index') }}"
                   class="adm-nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                       <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                   </svg>
                    Users
                    <x-admin.attention-badge
                        :count="$adminAttentionCounts['users'] ?? 0"
                        key-name="users"
                        label="Users"
                    />
                </a>
            </div>

            {{-- Finance --}}
            <div class="adm-nav-group">
                <div class="adm-nav-group-label">Finance</div>

                <a href="{{ route('admin.invoices.index') }}"
                   class="adm-nav-item {{ request()->routeIs('admin.invoices.*') ? 'active' : '' }}">
                   <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                       <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                   </svg>
                    Invoices
                    <x-admin.attention-badge
                        :count="$adminAttentionCounts['invoices'] ?? 0"
                        key-name="invoices"
                        label="Invoices"
                    />
                </a>

                <a href="{{ route('admin.coupons.index') }}"
                   class="adm-nav-item {{ request()->routeIs('admin.coupons.*') ? 'active' : '' }}">
                   <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                       <path stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                   </svg>
                    Coupons
                </a>

                <a href="{{ route('admin.insurances.index') }}"
                   class="adm-nav-item {{ request()->routeIs('admin.insurances.*') ? 'active' : '' }}">
                   <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                       <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                   </svg>
                    Insurances
                </a>
            </div>

            {{-- Content --}}
            <div class="adm-nav-group">
                <div class="adm-nav-group-label">Content</div>

                <a href="{{ route('admin.reviews.index') }}"
                   class="adm-nav-item {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                       <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                   </svg>
                    Reviews
                    <x-admin.attention-badge
                        :count="$adminAttentionCounts['reviews'] ?? 0"
                        key-name="reviews"
                        label="Reviews"
                    />
                </a>

                <a href="{{ route('admin.locations.index') }}"
                   class="adm-nav-item {{ request()->routeIs('admin.locations.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                       <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                       <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                   </svg>
                    Locations
                </a>
            </div>

            {{-- System --}}
            <div class="adm-nav-group">
                <div class="adm-nav-group-label">System</div>

                <a href="{{ route('admin.email-logs.index') }}"
                   class="adm-nav-item {{ request()->routeIs('admin.email-logs.*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                       <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                   </svg>
                    Email Logs
                </a>

                <a href="{{ route('admin.settings.index') }}"
                   class="adm-nav-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                   <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                       <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                       <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                   </svg>
                    Settings
                </a>
            </div>

        </nav>

        {{-- Footer --}}
        <div class="adm-sidebar-footer">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="adm-logout">
                   <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                       <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                   </svg>
                    Logout
                </button>
            </form>
        </div>

    </aside>

    {{-- ══ MAIN ══ --}}
    <main class="adm-main">
        @yield('content')
    </main>

</div>

@stack('scripts')
</body>
</html>
