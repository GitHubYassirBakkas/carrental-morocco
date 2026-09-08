<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | CarRental</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#07090f]">
<div class="flex min-h-screen">
    @php
        $adminAttentionCounts = $adminAttentionCounts ?? [];
    @endphp

    <!-- SIDEBAR -->
    <aside class="w-64 flex-shrink-0 bg-[#0b0d12] text-white flex flex-col border-r border-white/10">
        <div class="px-6 py-5 text-2xl font-bold text-yellow-400">
            CarRental Admin
        </div>

        <nav class="flex-1 px-4 space-y-1.5">
            <a href="{{ route('admin.dashboard') }}"
               class="adm-nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <span class="adm-nav-text">Dashboard</span>
            </a>

            <a href="{{ route('admin.cars.index') }}"
               class="adm-nav-item {{ request()->routeIs('admin.cars.*') ? 'active' : '' }}">
                <span class="adm-nav-text">Cars</span>
            </a>

             <a href="{{ route('admin.invoices.index') }}"
               class="adm-nav-item {{ request()->routeIs('admin.invoices.*') ? 'active' : '' }}">
                <span class="adm-nav-text">Invoices</span>
                <x-admin.attention-badge
                    :count="$adminAttentionCounts['invoices'] ?? 0"
                    key-name="invoices"
                    label="Invoices"
                />
            </a>

            <a href="{{ route('admin.refunds.index') }}"
               class="adm-nav-item {{ request()->routeIs('admin.refunds.*') ? 'active' : '' }}">
                <span class="adm-nav-text">Refunds</span>
            </a>

            <a href="{{ route('admin.insurances.index') }}"
               class="adm-nav-item {{ request()->routeIs('admin.insurances.*') ? 'active' : '' }}">
                <span class="adm-nav-text">Insurances</span>
            </a>

            <a href="{{ route('admin.bookings.index') }}"
               class="adm-nav-item {{ request()->routeIs('admin.bookings.*') ? 'active' : '' }}">
                <span class="adm-nav-text">Bookings</span>
                <x-admin.attention-badge
                    :count="$adminAttentionCounts['bookings'] ?? 0"
                    key-name="bookings"
                    label="Bookings"
                />
            </a>

            <a href="{{ route('admin.users.index') }}"
               class="adm-nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <span class="adm-nav-text">Users</span>
                <x-admin.attention-badge
                    :count="$adminAttentionCounts['users'] ?? 0"
                    key-name="users"
                    label="Users"
                />
            </a>
            
            <a href="{{ route('admin.email-logs.index') }}"
               class="adm-nav-item {{ request()->routeIs('admin.email-logs.*') ? 'active' : '' }}">
                <span class="adm-nav-text">Email Logs</span>
            </a>
            
            <a href="{{ route('admin.settings.index') }}"
               class="adm-nav-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                <span class="adm-nav-text">Settings</span>
            </a>
            
            <a href="{{ route('admin.reviews.index') }}"
               class="adm-nav-item {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}">
                <span class="adm-nav-text">Reviews</span>
                <x-admin.attention-badge
                    :count="$adminAttentionCounts['reviews'] ?? 0"
                    key-name="reviews"
                    label="Reviews"
                />
            </a>
            
            <a href="{{ route('admin.locations.index') }}"
               class="adm-nav-item {{ request()->routeIs('admin.locations.*') ? 'active' : '' }}">
                <span class="adm-nav-text">Locations</span>
            </a>
            
            <a href="{{ route('admin.coupons.index') }}"
               class="adm-nav-item {{ request()->routeIs('admin.coupons.*') ? 'active' : '' }}">
                <span class="adm-nav-text">Coupons</span>
            </a>

            <a href="{{ route('admin.support.index') }}"
               class="adm-nav-item {{ request()->routeIs('admin.support.*') ? 'active' : '' }}">
                <span class="adm-nav-text">Support</span>
                <x-admin.attention-badge
                    :count="$adminAttentionCounts['support'] ?? 0"
                    key-name="support"
                    label="Support"
                />
            </a>
        </nav>

        <div class="px-4 py-4 border-t border-gray-800">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full bg-red-600 hover:bg-red-700 py-2 rounded">
                    Logout
                </button>
            </form>
        </div>
    </aside>

    <!-- MAIN -->
<main class="flex-1 min-w-0 overflow-x-auto p-4 md:p-8 bg-[#07090f] min-h-screen">
    @yield('content')
    </main>

</div>



@stack('scripts')

<style>
    .adm-nav-item {
        display: flex;
        align-items: center;
        gap: 0.625rem;
        min-width: 0;
        border-radius: 0.5rem;
        border: 1px solid transparent;
        padding: 0.625rem 0.75rem;
        color: #d1d5db;
        font-size: 0.875rem;
        font-weight: 600;
        text-decoration: none;
        transition: background-color 150ms ease, border-color 150ms ease, color 150ms ease;
    }

    .adm-nav-item:hover {
        background: rgba(255, 255, 255, 0.06);
        border-color: rgba(255, 255, 255, 0.08);
        color: #fff;
    }

    .adm-nav-item.active {
        background: linear-gradient(90deg, rgba(200, 157, 102, 0.18), rgba(200, 157, 102, 0.06));
        border-color: rgba(200, 157, 102, 0.32);
        color: #facc15;
    }

    .adm-nav-text {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .adm-nav-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        min-width: 1.25rem;
        height: 1.25rem;
        margin-left: auto;
        border-radius: 999px;
        background: #dc2626;
        color: #fff;
        padding: 0 0.375rem;
        font-size: 0.6875rem;
        font-weight: 800;
        line-height: 1;
        box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.08);
    }

    @media (max-width: 900px) {
        .adm-nav-item {
            padding-inline: 0.625rem;
        }
    }
</style>

</body>
</html>
