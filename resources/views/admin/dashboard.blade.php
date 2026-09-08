@extends('admin.layouts.app')

@section('content')
<div class="db">

    {{-- ══ HEADER ══ --}}
    <div class="db-header">
        <div class="db-header-left">
            <div class="db-eyebrow">
                <span class="db-live-pulse"></span>
                Live Dashboard
            </div>
            <h1 class="db-title">Overview</h1>
        </div>
        <div class="db-header-right">
            <div class="db-time-block">
                <div class="db-time-val" id="db-clock">{{ now()->format('H:i') }}</div>
                <div class="db-time-date">{{ now()->format('D, d M Y') }}</div>
            </div>
            <a href="{{ route('admin.bookings.index') }}" class="db-action-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                Quick View
            </a>
        </div>
    </div>

    {{-- ══ DASHBOARD ALERTS ══ --}}
    <div class="db-section-title">{{ __('messages.admin_dashboard_alerts') }}</div>
    <div class="db-alerts-grid">
        {{-- Cars Returning Today --}}
        <a href="{{ route('admin.bookings.index', ['status' => 'active', 'end_date' => now()->toDateString()]) }}" class="db-alert-card db-alert-card--red">
            <div class="db-alert-card-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="db-alert-card-content">
                <div class="db-alert-card-count">{{ $endingTodayBookings }}</div>
                <div class="db-alert-card-label">{{ __('messages.admin_cars_returning_today') }}</div>
            </div>
            <div class="db-alert-card-badge db-alert-card-badge--red">{{ $endingTodayBookings }}</div>
        </a>

        {{-- Pickups Today --}}
        <a href="{{ route('admin.bookings.index', ['start_date' => now()->toDateString()]) }}" class="db-alert-card db-alert-card--green">
            <div class="db-alert-card-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="db-alert-card-content">
                <div class="db-alert-card-count">{{ $startingTodayBookings }}</div>
                <div class="db-alert-card-label">{{ __('messages.admin_pickups_today') }}</div>
            </div>
            <div class="db-alert-card-badge db-alert-card-badge--green">{{ $startingTodayBookings }}</div>
        </a>

        {{-- Drop-offs Today --}}
        <a href="{{ route('admin.bookings.index', ['end_date' => now()->toDateString()]) }}" class="db-alert-card db-alert-card--blue">
            <div class="db-alert-card-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="db-alert-card-content">
                <div class="db-alert-card-count">{{ $todayCheckOuts }}</div>
                <div class="db-alert-card-label">{{ __('messages.admin_dropoffs_today') }}</div>
            </div>
            <div class="db-alert-card-badge db-alert-card-badge--blue">{{ $todayCheckOuts }}</div>
        </a>

        {{-- Pending Confirmations --}}
        <a href="{{ route('admin.bookings.index', ['status' => 'pending']) }}" class="db-alert-card db-alert-card--amber">
            <div class="db-alert-card-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="db-alert-card-content">
                <div class="db-alert-card-count">{{ $pendingConfirmations }}</div>
                <div class="db-alert-card-label">{{ __('messages.admin_pending_confirmations') }}</div>
            </div>
            <div class="db-alert-card-badge db-alert-card-badge--amber">{{ $pendingConfirmations }}</div>
        </a>

        {{-- Active Rentals --}}
        <a href="{{ route('admin.bookings.index', ['status' => 'active']) }}" class="db-alert-card db-alert-card--purple">
            <div class="db-alert-card-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="db-alert-card-content">
                <div class="db-alert-card-count">{{ $activeRentals }}</div>
                <div class="db-alert-card-label">{{ __('messages.admin_active_rentals') }}</div>
            </div>
            <div class="db-alert-card-badge db-alert-card-badge--purple">{{ $activeRentals }}</div>
        </a>

        {{-- Security Deposits Waiting Release --}}
        @if($securityDepositsWaitingRelease > 0)
        <a href="{{ route('admin.bookings.index', ['status' => 'completed']) }}" class="db-alert-card db-alert-card--gold">
            <div class="db-alert-card-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <div class="db-alert-card-content">
                <div class="db-alert-card-count">{{ $securityDepositsWaitingRelease }}</div>
                <div class="db-alert-card-label">{{ __('messages.admin_deposits_waiting_release') }}</div>
            </div>
            <div class="db-alert-card-badge db-alert-card-badge--gold">{{ $securityDepositsWaitingRelease }}</div>
        </a>
        @endif
    </div>

    {{-- ══ PENDING ACTIONS ══ --}}
    <div class="db-section-title">{{ __('messages.admin_pending_actions') }}</div>
    <div class="db-actions-grid">
        {{-- Confirm Bookings --}}
        <a href="{{ route('admin.bookings.index', ['status' => 'pending']) }}" class="db-action-card">
            <div class="db-action-card-icon" style="--bg:rgba(245,158,11,0.12);--c:#fbbf24">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="db-action-card-content">
                <div class="db-action-card-title">{{ __('messages.admin_confirm_bookings') }}</div>
                <div class="db-action-card-count">{{ $bookingsToConfirm }}</div>
            </div>
        </a>

        {{-- Start Today's Rentals --}}
        <a href="{{ route('admin.bookings.index', ['status' => 'confirmed']) }}" class="db-action-card">
            <div class="db-action-card-icon" style="--bg:rgba(16,185,129,0.12);--c:#10b981">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <div class="db-action-card-content">
                <div class="db-action-card-title">{{ __('messages.admin_start_rentals') }}</div>
                <div class="db-action-card-count">{{ $rentalsToStart }}</div>
            </div>
        </a>

        {{-- Finish Today's Rentals --}}
        <a href="{{ route('admin.bookings.index', ['status' => 'active']) }}" class="db-action-card">
            <div class="db-action-card-icon" style="--bg:rgba(59,130,246,0.12);--c:#3b82f6">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div class="db-action-card-content">
                <div class="db-action-card-title">{{ __('messages.admin_finish_rentals') }}</div>
                <div class="db-action-card-count">{{ $rentalsToComplete }}</div>
            </div>
        </a>

        {{-- Release Deposits --}}
        @if($depositsToRelease > 0)
        <a href="{{ route('admin.bookings.index', ['status' => 'completed']) }}" class="db-action-card">
            <div class="db-action-card-icon" style="--bg:rgba(200,157,102,0.12);--c:var(--gold)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <div class="db-action-card-content">
                <div class="db-action-card-title">{{ __('messages.admin_release_deposits') }}</div>
                <div class="db-action-card-count">{{ $depositsToRelease }}</div>
            </div>
        </a>
        @endif

        {{-- Perform Check-ins --}}
        <a href="{{ route('admin.bookings.index', ['status' => 'confirmed']) }}" class="db-action-card">
            <div class="db-action-card-icon" style="--bg:rgba(139,92,246,0.12);--c:#a78bfa">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                </svg>
            </div>
            <div class="db-action-card-content">
                <div class="db-action-card-title">{{ __('messages.admin_perform_checkins') }}</div>
                <div class="db-action-card-count">{{ $checkinsToPerform }}</div>
            </div>
        </a>

        {{-- Perform Check-outs --}}
        <a href="{{ route('admin.bookings.index', ['status' => 'active']) }}" class="db-action-card">
            <div class="db-action-card-icon" style="--bg:rgba(236,72,153,0.12);--c:#ec4899">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
            </div>
            <div class="db-action-card-content">
                <div class="db-action-card-title">{{ __('messages.admin_perform_checkouts') }}</div>
                <div class="db-action-card-count">{{ $checkoutsToPerform }}</div>
            </div>
        </a>
    </div>

    {{-- ══ RECENT ACTIVITY ══ --}}
    <div class="db-section-title">{{ __('messages.admin_recent_activity') }}</div>
    <div class="db-card" style="margin-bottom:1.5rem">
        <div class="db-activity-timeline">
            @forelse($recentActivity as $activity)
                <div class="db-activity-item">
                    <div class="db-activity-icon db-activity-icon--{{ $activity->status }}">
                        @if($activity->status === 'pending')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        @elseif($activity->status === 'confirmed')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        @elseif($activity->status === 'active')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        @elseif($activity->status === 'completed')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        @else
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        @endif
                    </div>
                    <div class="db-activity-content">
                        <div class="db-activity-title">
                            @if($activity->status === 'pending')
                                {{ __('messages.admin_booking_created') }}
                            @elseif($activity->status === 'confirmed')
                                {{ __('messages.admin_booking_confirmed') }}
                            @elseif($activity->status === 'active')
                                {{ __('messages.admin_rental_started') }}
                            @elseif($activity->status === 'completed')
                                {{ __('messages.admin_rental_completed') }}
                            @else
                                {{ __('messages.admin_booking_created') }}
                            @endif
                        </div>
                        <div class="db-activity-meta">
                            <span class="db-activity-car">{{ $activity->car->full_name ?? 'Unknown' }}</span>
                            <span class="db-activity-time">{{ $activity->updated_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="db-empty">{{ __('messages.dashboard_no_bookings') }}</div>
            @endforelse
        </div>
    </div>

    {{-- ══ QUICK ACTIONS ══ --}}
    <div class="db-section-title">{{ __('messages.admin_quick_actions') }}</div>
    <div class="db-quick-actions">
        <a href="{{ route('admin.cars.create') }}" class="db-quick-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            {{ __('messages.admin_add_car') }}
        </a>
        <a href="{{ route('admin.insurances.create') }}" class="db-quick-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            {{ __('messages.admin_add_insurance') }}
        </a>
        <a href="{{ route('admin.coupons.create') }}" class="db-quick-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            {{ __('messages.admin_create_coupon') }}
        </a>
        <a href="{{ route('admin.bookings.index', ['start_date' => now()->toDateString()]) }}" class="db-quick-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            {{ __('messages.admin_view_bookings') }}
        </a>
        <a href="{{ route('admin.cars.index') }}" class="db-quick-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            {{ __('messages.admin_view_cars') }}
        </a>
        <a href="{{ route('admin.invoices.index') }}" class="db-quick-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            {{ __('messages.admin_view_invoices') }}
        </a>
        <a href="{{ route('admin.users.index') }}" class="db-quick-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            {{ __('messages.admin_view_users') }}
        </a>
    </div>

    {{-- ══ FLEET OVERVIEW ══ --}}
    <div class="db-section-title">{{ __('messages.admin_fleet_overview') }}</div>
    <div class="db-fleet-grid">
        <div class="db-fleet-card">
            <div class="db-fleet-icon" style="--bg:rgba(59,130,246,0.12);--c:#3b82f6">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
            </div>
            <div class="db-fleet-count">{{ $totalCars }}</div>
            <div class="db-fleet-label">{{ __('messages.admin_total_cars') }}</div>
        </div>
        <div class="db-fleet-card">
            <div class="db-fleet-icon" style="--bg:rgba(16,185,129,0.12);--c:#10b981">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="db-fleet-count">{{ $availableCars }}</div>
            <div class="db-fleet-label">{{ __('messages.admin_available_cars') }}</div>
        </div>
        <div class="db-fleet-card">
            <div class="db-fleet-icon" style="--bg:rgba(239,68,68,0.12);--c:#f87171">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
            </div>
            <div class="db-fleet-count">{{ $unavailableCars }}</div>
            <div class="db-fleet-label">{{ __('messages.admin_unavailable_cars') }}</div>
        </div>
    </div>

    {{-- ══ REVENUE SUMMARY ══ --}}
    <div class="db-section-title">{{ __('messages.admin_revenue_summary') }}</div>
    <div class="db-revenue-grid">
        <div class="db-revenue-card db-revenue-card--today">
            <div class="db-revenue-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="db-revenue-label">{{ __('messages.admin_today_revenue') }}</div>
            <div class="db-revenue-amount">{{ number_format($todayRevenue, 0) }} MAD</div>
        </div>
        <div class="db-revenue-card db-revenue-card--week">
            <div class="db-revenue-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="db-revenue-label">{{ __('messages.admin_weekly_revenue') }}</div>
            <div class="db-revenue-amount">{{ number_format($weeklyRevenue, 0) }} MAD</div>
        </div>
        <div class="db-revenue-card db-revenue-card--month">
            <div class="db-revenue-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="db-revenue-label">{{ __('messages.admin_monthly_revenue') }}</div>
            <div class="db-revenue-amount">{{ number_format($monthlyRevenue, 0) }} MAD</div>
        </div>
        <div class="db-revenue-card db-revenue-card--total">
            <div class="db-revenue-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="db-revenue-label">{{ __('messages.admin_total_revenue') }}</div>
            <div class="db-revenue-amount">{{ number_format($totalRevenue, 0) }} MAD</div>
        </div>
        <div class="db-revenue-card db-revenue-card--avg">
            <div class="db-revenue-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="db-revenue-label">{{ __('messages.admin_avg_booking_value') }}</div>
            <div class="db-revenue-amount">{{ number_format($averageBookingValue, 0) }} MAD</div>
        </div>
        <div class="db-revenue-card db-revenue-card--completed">
            <div class="db-revenue-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="db-revenue-label">{{ __('messages.admin_total_completed') }}</div>
            <div class="db-revenue-amount">{{ $completedRentals }}</div>
        </div>
    </div>

    {{-- ══ BOOKING CALENDAR ══ --}}
    <div class="db-section-title">{{ __('messages.admin_booking_calendar') }}</div>
    <div class="db-calendar-grid">
        <div class="db-calendar-card">
            <div class="db-calendar-icon" style="--bg:rgba(16,185,129,0.12);--c:#10b981">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div class="db-calendar-count">{{ $todayCheckIns }}</div>
            <div class="db-calendar-label">{{ __('messages.admin_todays_pickups') }}</div>
        </div>
        <div class="db-calendar-card">
            <div class="db-calendar-icon" style="--bg:rgba(59,130,246,0.12);--c:#3b82f6">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
            </div>
            <div class="db-calendar-count">{{ $todayCheckOuts }}</div>
            <div class="db-calendar-label">{{ __('messages.admin_todays_dropoffs') }}</div>
        </div>
        <div class="db-calendar-card">
            <div class="db-calendar-icon" style="--bg:rgba(139,92,246,0.12);--c:#a78bfa">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="db-calendar-count">{{ $activeRentals }}</div>
            <div class="db-calendar-label">{{ __('messages.admin_todays_active') }}</div>
        </div>
    </div>

    {{-- ══ DASHBOARD NOTIFICATIONS ══ --}}
    <div class="db-section-title">{{ __('messages.admin_dashboard_notifications') }}</div>
    <div class="db-notifications">
        @forelse($adminNotifications as $notification)
            <a href="{{ $notification['link'] ?? '#' }}" class="db-notification-item">
                <div class="db-notification-icon db-notification-icon--{{ $notification['type'] }}">
                    @if($notification['type'] === 'new_booking')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    @elseif($notification['type'] === 'booking_cancelled')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                    @elseif($notification['type'] === 'new_review')
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                        </svg>
                    @elseif($notification['type'] === 'overdue_rental')
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    @elseif($notification['type'] === 'failed_email')
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    @elseif($notification['type'] === 'new_damage_report')
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    @elseif($notification['type'] === 'new_support_ticket')
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                    @else
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    @endif
                </div>
                <div class="db-notification-content">
                    <div class="db-notification-title">
                        {{ $notification['title'] }}
                        @if(isset($notification['priority']))
                            <span class="db-notification-badge db-notification-badge--{{ $notification['priority'] }}">
                                {{ ucfirst($notification['priority']) }}
                            </span>
                        @endif
                    </div>
                    <div class="db-notification-message">{{ $notification['message'] }}</div>
                    <div class="db-notification-time">{{ $notification['time'] }}</div>
                </div>
            </a>
        @empty
            <div class="db-empty">No administrative notifications.</div>
        @endforelse
    </div>

    {{-- ══ CHARTS ══ --}}
    <div class="db-charts">
        <div class="db-chart-box db-chart-box--wide">
            <div class="db-chart-top">
                <div class="db-chart-ttl">
                    <div class="db-dot db-dot--green"></div>
                    Revenue <span>{{ now()->year }}</span>
                </div>
                <div class="db-chart-meta">Monthly</div>
            </div>
            <div class="db-chart-area"><canvas id="revenueChart"></canvas></div>
        </div>
        <div class="db-chart-box">
            <div class="db-chart-top">
                <div class="db-chart-ttl">
                    <div class="db-dot db-dot--blue"></div>
                    Booking Status
                </div>
                <div class="db-chart-meta">Overview</div>
            </div>
            <div class="db-chart-area"><canvas id="bookingStatusChart"></canvas></div>
        </div>
    </div>

    {{-- ══ LIFECYCLE ══ --}}
    <div class="db-card db-lifecycle">
        <div class="db-card-head">
            <div class="db-dot db-dot--purple"></div>
            Booking Lifecycle
        </div>
        <div class="db-lc-grid">
            @foreach($bookingLifecycle as $status => $count)
            <div class="db-lc-item">
                <div class="db-lc-label">{{ ucfirst(str_replace('_', ' ', $status)) }}</div>
                <div class="db-lc-track">
                    <div class="db-lc-fill db-lc-fill--{{ $status }}"
                         style="width:{{ $count > 0 ? max(3, ($count / max(1,$totalBookings))*100) : 0 }}%"></div>
                </div>
                <div class="db-lc-num">{{ $count }}</div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ══ TABLES ══ --}}
    <div class="db-tables">
        <div class="db-card">
            <div class="db-card-head"><div class="db-dot db-dot--gold"></div>Top Rented Cars</div>
            <table class="db-tbl">
                <thead><tr><th>#</th><th>Car</th><th>Rentals</th></tr></thead>
                <tbody>
                    @forelse($topRentedCars as $i => $item)
                    <tr>
                        <td><span class="db-rank {{ $i < 3 ? 'db-rank--top' : '' }}">{{ $i+1 }}</span></td>
                        <td>{{ $item->car->full_name ?? 'Unknown' }}</td>
                        <td><span class="db-pill db-pill--gold">{{ $item->total_rentals }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="db-empty">No rentals yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="db-card">
            <div class="db-card-head"><div class="db-dot db-dot--red"></div>High Demand (Unavailable)</div>
            <table class="db-tbl">
                <thead><tr><th>Car</th><th>Rentals</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($lowAvailabilityCars as $car)
                    <tr>
                        <td>{{ $car->full_name }}</td>
                        <td><span class="db-pill db-pill--amber">{{ $car->bookings_count }}</span></td>
                        <td><span class="db-pill db-pill--red">Unavailable</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="db-empty">All cars available ✓</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="db-card" style="margin-bottom:2rem">
        <div class="db-card-head"><div class="db-dot db-dot--purple"></div>Top Customers</div>
        <table class="db-tbl">
            <thead><tr><th>Rank</th><th>Customer</th><th>Bookings</th></tr></thead>
            <tbody>
                @forelse($topCustomers as $i => $customer)
                <tr>
                    <td><span class="db-rank {{ $i < 3 ? 'db-rank--top' : '' }}">#{{ $i+1 }}</span></td>
                    <td>{{ $customer->name }}</td>
                    <td><span class="db-pill db-pill--purple">{{ $customer->bookings_count }}</span></td>
                </tr>
                @empty
                <tr><td colspan="3" class="db-empty">No customers yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ══ NEW ADMIN CARDS ══ --}}
    <div class="db-section-title">Quick Stats</div>
    <div class="db-tables" style="margin-bottom: 2rem;">
        {{-- Upcoming Pickups --}}
        <div class="db-card">
            <div class="db-card-head"><div class="db-dot db-dot--green"></div>Upcoming Pickups</div>
            <div style="padding: 1rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span style="color: var(--muted); font-size: 0.75rem;">Today</span>
                    <span style="font-weight: 700; color: var(--txt);">{{ $upcomingPickupsToday }}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--muted); font-size: 0.75rem;">Tomorrow</span>
                    <span style="font-weight: 700; color: var(--txt);">{{ $upcomingPickupsTomorrow }}</span>
                </div>
            </div>
        </div>

        {{-- Upcoming Returns --}}
        <div class="db-card">
            <div class="db-card-head"><div class="db-dot db-dot--red"></div>Upcoming Returns</div>
            <div style="padding: 1rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span style="color: var(--muted); font-size: 0.75rem;">Today</span>
                    <span style="font-weight: 700; color: var(--txt);">{{ $upcomingReturnsToday }}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--muted); font-size: 0.75rem;">Tomorrow</span>
                    <span style="font-weight: 700; color: var(--txt);">{{ $upcomingReturnsTomorrow }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Latest Bookings --}}
    <div class="db-card" style="margin-bottom: 2rem;">
        <div class="db-card-head"><div class="db-dot db-dot--blue"></div>Latest Bookings</div>
        <table class="db-tbl">
            <thead><tr><th>Customer</th><th>Car</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
                @forelse($latestBookings as $booking)
                <tr>
                    <td>{{ $booking->user->name ?? 'Unknown' }}</td>
                    <td>{{ $booking->car->full_name ?? 'Unknown' }}</td>
                    <td><x-admin.booking-status-badge :status="$booking->status" class="px-2 py-0.5" /></td>
                    <td style="color: var(--muted); font-size: 0.72rem;">{{ $booking->created_at->format('M d, Y') }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="db-empty">No bookings yet</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

<style>
.db {
    --bg:    #07090f;
    --bg2:   #0c0f18;
    --bg3:   #111520;
    --bdr:   rgba(255,255,255,0.055);
    --bdr2:  rgba(255,255,255,0.1);
    --txt:   #e8eaf0;
    --muted: #52596e;
    --hint:  #2a3045;
    --gold:  #C89D66;
    background: var(--bg);
    color: var(--txt);
    font-family: 'DM Sans', system-ui, sans-serif;
    padding: 1.75rem 2rem;
    min-height: 100%;
}

/* ── SECTION TITLE ── */
.db-section-title {
    font-size: 0.85rem; font-weight: 700; color: var(--muted);
    text-transform: uppercase; letter-spacing: 0.12em;
    margin-bottom: 0.75rem;
}

/* ── ALERTS GRID ── */
.db-alerts-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 10px; margin-bottom: 1.5rem;
}
@media(max-width:760px) { .db-alerts-grid{ grid-template-columns: repeat(2,1fr); } }
@media(max-width:480px) { .db-alerts-grid{ grid-template-columns: 1fr; } }

.db-alert-card {
    display: flex; align-items: center; gap: 12px;
    background: var(--bg2); border: 1px solid var(--bdr);
    border-radius: 10px; padding: 1rem;
    text-decoration: none;
    transition: all 0.2s;
    animation: db-up 0.35s ease both;
    position: relative; overflow: hidden;
}
.db-alert-card:hover { border-color: var(--accent); transform: translateY(-2px); }

.db-alert-card-icon {
    width: 36px; height: 36px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.db-alert-card--red .db-alert-card-icon { background: rgba(239,68,68,0.12); color: #f87171; }
.db-alert-card--green .db-alert-card-icon { background: rgba(16,185,129,0.12); color: #10b981; }
.db-alert-card--blue .db-alert-card-icon { background: rgba(59,130,246,0.12); color: #3b82f6; }
.db-alert-card--amber .db-alert-card-icon { background: rgba(245,158,11,0.12); color: #fbbf24; }
.db-alert-card--purple .db-alert-card-icon { background: rgba(139,92,246,0.12); color: #a78bfa; }
.db-alert-card--gray .db-alert-card-icon { background: rgba(107,114,128,0.12); color: #9ca3af; }
.db-alert-card--gold .db-alert-card-icon { background: rgba(200,157,102,0.12); color: var(--gold); }
.db-alert-card--orange .db-alert-card-icon { background: rgba(249,115,22,0.12); color: #fb923c; }

.db-alert-card-icon svg { width: 16px; height: 16px; }

.db-alert-card-content { flex: 1; }
.db-alert-card-count {
    font-size: 1.4rem; font-weight: 800; color: var(--txt);
    letter-spacing: -0.04em; line-height: 1;
}
.db-alert-card-label {
    font-size: 0.65rem; color: var(--muted);
    text-transform: uppercase; letter-spacing: 0.08em; margin-top: 2px;
}

.db-alert-card-badge {
    font-size: 0.6rem; font-weight: 700; padding: 2px 8px;
    border-radius: 100px; letter-spacing: 0.04em;
}
.db-alert-card-badge--red { background: rgba(239,68,68,0.15); color: #f87171; }
.db-alert-card-badge--green { background: rgba(16,185,129,0.15); color: #10b981; }
.db-alert-card-badge--blue { background: rgba(59,130,246,0.15); color: #3b82f6; }
.db-alert-card-badge--amber { background: rgba(245,158,11,0.15); color: #fbbf24; }
.db-alert-card-badge--purple { background: rgba(139,92,246,0.15); color: #a78bfa; }
.db-alert-card-badge--gray { background: rgba(107,114,128,0.15); color: #9ca3af; }
.db-alert-card-badge--gold { background: rgba(200,157,102,0.15); color: var(--gold); }
.db-alert-card-badge--orange { background: rgba(249,115,22,0.15); color: #fb923c; }

/* ── HEADER ── */
.db-header {
    display: flex; justify-content: space-between; align-items: flex-start;
    margin-bottom: 1.75rem; gap: 1rem;
}
.db-eyebrow {
    display: flex; align-items: center; gap: 7px;
    font-size: 0.65rem; font-weight: 700;
    color: var(--muted); letter-spacing: 0.18em;
    text-transform: uppercase; margin-bottom: 5px;
}
.db-live-pulse {
    width: 6px; height: 6px; border-radius: 50%;
    background: #10b981;
    box-shadow: 0 0 0 0 rgba(16,185,129,0.4);
    animation: db-ping 2s ease infinite;
}
@keyframes db-ping {
    0%   { box-shadow: 0 0 0 0 rgba(16,185,129,0.4); }
    70%  { box-shadow: 0 0 0 7px rgba(16,185,129,0); }
    100% { box-shadow: 0 0 0 0 rgba(16,185,129,0); }
}
.db-title {
    font-size: clamp(1.6rem, 3vw, 2.4rem);
    font-weight: 900; letter-spacing: -0.05em;
    color: var(--txt); line-height: 1;
}
.db-header-right { display: flex; align-items: center; gap: 12px; }

.db-time-block { text-align: right; }
.db-time-val {
    font-size: 1.4rem; font-weight: 800; color: var(--txt);
    letter-spacing: -0.04em; line-height: 1;
}
.db-time-date { font-size: 0.68rem; color: var(--muted); margin-top: 2px; }

.db-action-btn {
    display: flex; align-items: center; gap: 7px;
    padding: 8px 16px;
    background: rgba(200,157,102,0.08);
    border: 1px solid rgba(200,157,102,0.2);
    border-radius: 8px; color: var(--gold);
    font-size: 0.78rem; font-weight: 700;
    text-decoration: none; letter-spacing: 0.02em;
    transition: all 0.2s;
}
.db-action-btn svg { width: 14px; height: 14px; }
.db-action-btn:hover { background: rgba(200,157,102,0.14); border-color: rgba(200,157,102,0.4); }

/* ── ALERTS ── */
.db-alerts { display: flex; flex-direction: column; gap: 8px; margin-bottom: 1.5rem; }

.db-alert {
    display: flex; align-items: stretch; gap: 0;
    background: var(--bg2);
    border: 1px solid var(--bdr);
    border-radius: 10px; overflow: hidden;
    animation: db-up 0.35s ease both;
}
@keyframes db-up { from{opacity:0;transform:translateY(6px)} to{opacity:1;transform:none} }

.db-alert-stripe { width: 3px; flex-shrink: 0; background: #ef4444; }
.db-alert-stripe--amber { background: #f59e0b; }

.db-alert-icon-wrap {
    width: 44px; display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; color: #f87171;
}
.db-alert-icon-wrap--amber { color: #fbbf24; }
.db-alert-icon-wrap svg { width: 18px; height: 18px; }

.db-alert-content { flex: 1; padding: 10px 14px 10px 4px; }

.db-alert-head {
    display: flex; align-items: center; gap: 8px;
    font-size: 0.78rem; font-weight: 700; color: #fca5a5;
    margin-bottom: 8px;
}
.db-alert--amber .db-alert-head { color: #fde68a; }

.db-alert-pill {
    font-size: 0.6rem; padding: 1px 8px; border-radius: 100px;
    font-weight: 800;
}
.db-alert-pill--red   { background: rgba(239,68,68,0.15);  color: #f87171; }
.db-alert-pill--amber { background: rgba(245,158,11,0.15); color: #fbbf24; }

.db-alert-rows { display: flex; flex-direction: column; gap: 4px; }
.db-alert-row {
    display: flex; align-items: center; gap: 10px;
    padding: 5px 8px; border-radius: 6px; font-size: 0.75rem;
    background: rgba(255,255,255,0.02);
}
.db-alert-id   { font-weight: 700; color: var(--txt); font-family: monospace; }
.db-alert-tag  { color: var(--muted); flex: 1; }
.db-alert-fee  { color: #f87171; font-weight: 700; }
.db-alert-cta  {
    padding: 3px 10px; border-radius: 5px; font-size: 0.65rem; font-weight: 700;
    text-decoration: none; background: rgba(239,68,68,0.15); color: #f87171;
    margin-left: auto;
}
.db-alert-cta--amber { background: rgba(245,158,11,0.15); color: #fbbf24; }

/* ── KPIs ── */
.db-kpis {
    display: grid; grid-template-columns: repeat(5,1fr);
    gap: 10px; margin-bottom: 10px;
}
@media(max-width:1280px){ .db-kpis{ grid-template-columns: repeat(3,1fr); } }
@media(max-width:760px) { .db-kpis{ grid-template-columns: repeat(2,1fr); } }

.db-kpi {
    position: relative; overflow: hidden;
    background: var(--bg2);
    border: 1px solid var(--bdr);
    border-radius: 12px; padding: 1.1rem;
    transition: border-color 0.2s, transform 0.2s;
    animation: db-up 0.4s ease both;
}
.db-kpi:hover { border-color: var(--accent); transform: translateY(-2px); }

.db-kpi-glow {
    position: absolute; inset: 0;
    background: radial-gradient(circle at 0% 0%, var(--glow) 0%, transparent 60%);
    pointer-events: none;
}

.db-kpi-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }

.db-kpi-icon {
    width: 34px; height: 34px; border-radius: 8px;
    background: var(--ic-bg); color: var(--ic-c);
    display: flex; align-items: center; justify-content: center;
}
.db-kpi-icon svg { width: 16px; height: 16px; }

.db-kpi-chip {
    font-size: 0.6rem; font-weight: 700; padding: 2px 8px;
    border-radius: 100px; letter-spacing: 0.04em;
    background: var(--chip-bg); color: var(--chip-c);
}

.db-kpi-num {
    font-size: clamp(1.4rem, 2.5vw, 2rem);
    font-weight: 900; color: var(--txt);
    letter-spacing: -0.05em; line-height: 1; margin-bottom: 3px;
}
.db-kpi-num--gold { color: var(--gold); }

.db-kpi-label { font-size: 0.7rem; color: var(--muted); margin-bottom: 10px; }

.db-kpi-track { height: 2px; background: rgba(255,255,255,0.05); border-radius: 2px; overflow: hidden; }
.db-kpi-fill  { height: 100%; border-radius: 2px; transition: width 1s ease; }

/* ── SECONDARY ── */
.db-sec-row {
    display: flex; gap: 10px; flex-wrap: wrap;
    margin-bottom: 1.25rem;
}
.db-sec {
    flex: 1; min-width: 150px;
    background: var(--bg2); border: 1px solid var(--bdr);
    border-radius: 10px; padding: 0.875rem 1rem;
    display: flex; align-items: center; gap: 12px;
    animation: db-up 0.4s ease both;
}
.db-sec--warn { border-color: rgba(239,68,68,0.15); }
.db-sec--blue { border-color: rgba(59,130,246,0.15); }

.db-sec-ico {
    width: 34px; height: 34px; border-radius: 8px; flex-shrink: 0;
    background: rgba(255,255,255,0.04);
    display: flex; align-items: center; justify-content: center;
    color: var(--c);
}
.db-sec-ico svg { width: 15px; height: 15px; }

.db-sec-val { font-size: 1.3rem; font-weight: 800; color: var(--txt); letter-spacing: -0.04em; line-height: 1; }
.db-sec-lbl { font-size: 0.65rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.08em; margin-top: 2px; }
.db-sec-sub { font-size: 0.65rem; color: var(--hint); margin-top: 2px; }

/* ── CHARTS ── */
.db-charts {
    display: grid; grid-template-columns: 1.6fr 1fr;
    gap: 10px; margin-bottom: 10px;
}
@media(max-width:960px) { .db-charts{ grid-template-columns: 1fr; } }

.db-chart-box {
    background: var(--bg2); border: 1px solid var(--bdr);
    border-radius: 12px; padding: 1.1rem;
    animation: db-up 0.5s ease both;
}

.db-chart-top {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 1rem;
}
.db-chart-ttl {
    display: flex; align-items: center; gap: 8px;
    font-size: 0.82rem; font-weight: 700; color: var(--txt);
}
.db-chart-ttl span { color: var(--muted); font-weight: 400; }
.db-chart-meta {
    font-size: 0.6rem; padding: 2px 9px; border-radius: 100px;
    background: rgba(255,255,255,0.04); border: 1px solid var(--bdr);
    color: var(--muted); letter-spacing: 0.06em;
}

.db-chart-area { height: 260px; position: relative; }

/* ── ACTIONS GRID ── */
.db-actions-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 10px; margin-bottom: 1.5rem;
}
@media(max-width:760px) { .db-actions-grid{ grid-template-columns: repeat(2,1fr); } }
@media(max-width:480px) { .db-actions-grid{ grid-template-columns: 1fr; } }

.db-action-card {
    display: flex; align-items: center; gap: 12px;
    background: var(--bg2); border: 1px solid var(--bdr);
    border-radius: 10px; padding: 0.875rem 1rem;
    text-decoration: none;
    transition: all 0.2s;
    animation: db-up 0.35s ease both;
}
.db-action-card:hover { border-color: var(--gold); transform: translateY(-2px); }

.db-action-card-icon {
    width: 32px; height: 32px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    background: var(--bg); color: var(--c);
}
.db-action-card-icon svg { width: 14px; height: 14px; }

.db-action-card-content { flex: 1; }
.db-action-card-title {
    font-size: 0.7rem; font-weight: 700; color: var(--txt);
    text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 2px;
}
.db-action-card-count {
    font-size: 1.1rem; font-weight: 800; color: var(--gold);
    letter-spacing: -0.04em; line-height: 1;
}

/* ── DOTS ── */
.db-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.db-dot--green  { background: #10b981; box-shadow: 0 0 5px #10b981; }
.db-dot--blue   { background: #3b82f6; box-shadow: 0 0 5px #3b82f6; }
.db-dot--purple { background: #8b5cf6; }
.db-dot--gold   { background: var(--gold); }
.db-dot--red    { background: #ef4444; }

/* ── CARD ── */
.db-card {
    background: var(--bg2); border: 1px solid var(--bdr);
    border-radius: 12px; overflow: hidden;
    animation: db-up 0.5s ease both;
}
.db-card-head {
    display: flex; align-items: center; gap: 8px;
    padding: 0.875rem 1.1rem;
    border-bottom: 1px solid var(--bdr);
    font-size: 0.82rem; font-weight: 700; color: var(--txt);
}

/* ── LIFECYCLE ── */
.db-lifecycle { margin-bottom: 10px; }
.db-lc-grid { padding: 0.875rem 1.1rem; display: flex; flex-direction: column; gap: 10px; }
.db-lc-item { display: flex; align-items: center; gap: 10px; }
.db-lc-label { font-size: 0.72rem; color: var(--muted); width: 100px; flex-shrink: 0; text-transform: capitalize; }
.db-lc-track { flex: 1; height: 4px; background: rgba(255,255,255,0.04); border-radius: 2px; overflow: hidden; }
.db-lc-fill  { height: 100%; border-radius: 2px; transition: width 0.8s ease; }
.db-lc-fill--pending      { background: #f59e0b; }
.db-lc-fill--confirmed    { background: #3b82f6; }
.db-lc-fill--active       { background: #10b981; }
.db-lc-fill--ending_today { background: #f97316; }
.db-lc-fill--completed    { background: #52596e; }
.db-lc-num { font-size: 0.72rem; font-weight: 700; color: var(--txt); width: 24px; text-align: right; }

/* ── TABLES ── */
.db-tables { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px; }
@media(max-width:900px) { .db-tables { grid-template-columns: 1fr; } }

.db-tbl { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
.db-tbl thead th {
    padding: 7px 14px;
    background: rgba(255,255,255,0.02);
    color: var(--muted); font-size: 0.6rem;
    font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em;
    border-bottom: 1px solid var(--bdr); text-align: left;
}
.db-tbl tbody tr { transition: background 0.12s; }
.db-tbl tbody tr:hover { background: rgba(255,255,255,0.018); }
.db-tbl tbody td {
    padding: 9px 14px; color: var(--txt);
    border-bottom: 1px solid rgba(255,255,255,0.025);
}

.db-rank {
    display: inline-flex; align-items: center; justify-content: center;
    width: 20px; height: 20px; border-radius: 5px;
    background: rgba(255,255,255,0.04); color: var(--muted);
    font-size: 0.62rem; font-weight: 700;
}
.db-rank--top { background: rgba(200,157,102,0.15); color: var(--gold); }

.db-empty { color: var(--muted); font-style: italic; text-align: center; padding: 2rem; font-size: 0.78rem; }

.db-pill {
    display: inline-block; padding: 2px 9px;
    border-radius: 100px; font-size: 0.67rem; font-weight: 700;
}
.db-pill--gold   { background: rgba(200,157,102,0.12); color: var(--gold); }
.db-pill--amber  { background: rgba(245,158,11,0.12);  color: #f59e0b; }
.db-pill--red    { background: rgba(239,68,68,0.12);   color: #f87171; }
.db-pill--purple { background: rgba(139,92,246,0.12);  color: #a78bfa; }
.db-pill--green  { background: rgba(16,185,129,0.12);  color: #10b981; }

/* ── ACTIVITY TIMELINE ── */
.db-activity-timeline { padding: 1rem; display: flex; flex-direction: column; gap: 12px; }
.db-activity-item { display: flex; align-items: center; gap: 12px; }
.db-activity-icon {
    width: 32px; height: 32px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.db-activity-icon svg { width: 14px; height: 14px; }
.db-activity-icon--pending { background: rgba(245,158,11,0.12); color: #fbbf24; }
.db-activity-icon--confirmed { background: rgba(16,185,129,0.12); color: #10b981; }
.db-activity-icon--active { background: rgba(59,130,246,0.12); color: #3b82f6; }
.db-activity-icon--completed { background: rgba(139,92,246,0.12); color: #a78bfa; }
.db-activity-content { flex: 1; }
.db-activity-title { font-size: 0.78rem; font-weight: 700; color: var(--txt); margin-bottom: 2px; }
.db-activity-meta { display: flex; align-items: center; gap: 8px; font-size: 0.7rem; color: var(--muted); }
.db-activity-car { font-weight: 600; }

/* ── QUICK ACTIONS ── */
.db-quick-actions { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 8px; }
@media(max-width:760px) { .db-quick-actions{ grid-template-columns: repeat(2,1fr); } }
@media(max-width:480px) { .db-quick-actions{ grid-template-columns: 1fr; } }
.db-quick-btn {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    padding: 0.75rem 1rem;
    background: var(--bg2); border: 1px solid var(--bdr);
    border-radius: 8px; color: var(--txt);
    font-size: 0.72rem; font-weight: 600;
    text-decoration: none; transition: all 0.2s;
}
.db-quick-btn:hover { border-color: var(--gold); background: rgba(200,157,102,0.08); }
.db-quick-btn svg { width: 14px; height: 14px; }

/* ── FLEET GRID ── */
.db-fleet-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 8px; margin-bottom: 1.5rem; }
@media(max-width:760px) { .db-fleet-grid{ grid-template-columns: repeat(2,1fr); } }
@media(max-width:480px) { .db-fleet-grid{ grid-template-columns: 1fr; } }
.db-fleet-card {
    display: flex; flex-direction: column; align-items: center; gap: 10px;
    background: var(--bg2); border: 1px solid var(--bdr);
    border-radius: 10px; padding: 1rem;
    text-align: center;
}
.db-fleet-icon { width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
.db-fleet-icon svg { width: 16px; height: 16px; }
.db-fleet-count { font-size: 1.4rem; font-weight: 800; color: var(--txt); letter-spacing: -0.04em; }
.db-fleet-label { font-size: 0.65rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.08em; }

/* ── REVENUE GRID ── */
.db-revenue-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 8px; margin-bottom: 1.5rem; }
@media(max-width:760px) { .db-revenue-grid{ grid-template-columns: repeat(2,1fr); } }
@media(max-width:480px) { .db-revenue-grid{ grid-template-columns: 1fr; } }
.db-revenue-card {
    display: flex; flex-direction: column; align-items: center; gap: 10px;
    background: var(--bg2); border: 1px solid var(--bdr);
    border-radius: 10px; padding: 1rem;
    text-align: center;
}
.db-revenue-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--gold); }
.db-revenue-icon svg { width: 14px; height: 14px; }
.db-revenue-label { font-size: 0.65rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.08em; }
.db-revenue-amount { font-size: 1.2rem; font-weight: 800; color: var(--gold); letter-spacing: -0.04em; }

/* ── CALENDAR GRID ── */
.db-calendar-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 1.5rem; }
@media(max-width:760px) { .db-calendar-grid{ grid-template-columns: 1fr; } }
.db-calendar-card {
    display: flex; flex-direction: column; align-items: center; gap: 10px;
    background: var(--bg2); border: 1px solid var(--bdr);
    border-radius: 10px; padding: 1rem;
    text-align: center;
}
.db-calendar-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
.db-calendar-icon svg { width: 14px; height: 14px; }
.db-calendar-count { font-size: 1.2rem; font-weight: 800; color: var(--txt); letter-spacing: -0.04em; }
.db-calendar-label { font-size: 0.65rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.08em; }

/* ── NOTIFICATIONS ── */
.db-notifications { display: flex; flex-direction: column; gap: 8px; }
.db-notification-item {
    display: flex; align-items: center; gap: 12px;
    background: var(--bg2); border: 1px solid var(--bdr);
    border-radius: 10px; padding: 1rem;
}
.db-notification-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.db-notification-icon svg { width: 14px; height: 14px; }
.db-notification-icon--booking_approved { background: rgba(16,185,129,0.12); color: #10b981; }
.db-notification-icon--payment_success { background: rgba(59,130,246,0.12); color: #3b82f6; }
.db-notification-icon--booking_cancelled { background: rgba(239,68,68,0.12); color: #f87171; }
.db-notification-icon--security_deposit_released { background: rgba(200,157,102,0.12); color: var(--gold); }
.db-notification-icon--new_booking { background: rgba(59,130,246,0.12); color: #3b82f6; }
.db-notification-icon--deposit_released { background: rgba(200,157,102,0.12); color: var(--gold); }
.db-notification-icon--new_review { background: rgba(245,158,11,0.12); color: #fbbf24; }
.db-notification-icon--overdue_rental { background: rgba(239,68,68,0.12); color: #f87171; }
.db-notification-icon--failed_email { background: rgba(239,68,68,0.12); color: #f87171; }
.db-notification-icon--new_damage_report { background: rgba(239,68,68,0.12); color: #f87171; }
.db-notification-icon--new_support_ticket { background: rgba(245,158,11,0.12); color: #fbbf24; }
.db-notification-content { flex: 1; }
.db-notification-title { font-size: 0.78rem; font-weight: 700; color: var(--txt); margin-bottom: 2px; display: flex; align-items: center; gap: 6px; }
.db-notification-message { font-size: 0.7rem; color: var(--muted); margin-bottom: 4px; }
.db-notification-time { font-size: 0.65rem; color: var(--hint); }
.db-notification-badge {
    font-size: 0.6rem; padding: 2px 8px; border-radius: 100px;
    font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em;
}
.db-notification-badge--critical { background: rgba(239,68,68,0.15); color: #f87171; }
.db-notification-badge--warning { background: rgba(245,158,11,0.15); color: #fbbf24; }
.db-notification-badge--info { background: rgba(59,130,246,0.15); color: #3b82f6; }
</style>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Live clock
setInterval(() => {
    const now = new Date();
    const el = document.getElementById('db-clock');
    if (el) el.textContent = now.getHours().toString().padStart(2,'0') + ':' + now.getMinutes().toString().padStart(2,'0');
}, 1000);

// Wait for Chart.js to load
document.addEventListener('DOMContentLoaded', function() {
    // Check if Chart is loaded, if not wait for it
    function initCharts() {
        if (typeof Chart === 'undefined') {
            setTimeout(initCharts, 100);
            return;
        }

        if (typeof Chart === 'undefined') {
            console.error('Chart.js is not loaded!');
            return;
        }

        const rCtx = document.getElementById('revenueChart');
        const sCtx = document.getElementById('bookingStatusChart');

        const revenueData = @json($monthlyChart->toArray());
        const statusData = @json($bookingStatusChart->toArray());

        Chart.defaults.color = '#52596e';
        Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';
        Chart.defaults.font.family = 'DM Sans, system-ui, sans-serif';
        Chart.defaults.font.size = 11;

        // Revenue Chart
        if (rCtx) {
            const revenueLabels = Object.keys(revenueData);
            const revenueValues = Object.values(revenueData);

            const hasData = Object.values(revenueData).some(v => v > 0);

            if (hasData) {
                try {
                    new Chart(rCtx, {
                        type: 'bar',
                        data: {
                            labels: revenueLabels,
                            datasets: [{
                                label: 'Revenue (MAD)',
                                data: revenueValues,
                                backgroundColor: function(ctx) {
                                    const g = ctx.chart.ctx.createLinearGradient(0,0,0,240);
                                    g.addColorStop(0,'rgba(16,185,129,0.75)');
                                    g.addColorStop(1,'rgba(16,185,129,0.05)');
                                    return g;
                                },
                                borderColor: 'rgba(16,185,129,0.6)',
                                borderWidth: 1, borderRadius: 6, borderSkipped: false,
                            }]
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.04)' } },
                                x: { grid: { display: false } }
                            }
                        }
                    });
                } catch (e) {
                    console.error('Error creating revenue chart:', e);
                }
            } else {
                rCtx.parentElement.innerHTML = '<div class="db-empty">No revenue data available.</div>';
            }
        }

        // Booking Status Chart
        if (sCtx) {
            const statusLabels = Object.keys(statusData).map(k => k.charAt(0).toUpperCase() + k.slice(1));
            const statusValues = Object.values(statusData);

            const hasData = Object.values(statusData).some(v => v > 0);

            if (hasData) {
                try {
                    new Chart(sCtx, {
                        type: 'doughnut',
                        data: {
                            labels: statusLabels,
                            datasets: [{
                                data: statusValues,
                                backgroundColor: [
                                    'rgba(59,130,246,0.8)','rgba(82,89,110,0.8)',
                                    'rgba(239,68,68,0.8)','rgba(245,158,11,0.8)','rgba(16,185,129,0.8)'
                                ],
                                borderColor: '#0c0f18', borderWidth: 3
                            }]
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            cutout: '70%',
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: { padding: 14, color: '#52596e', boxWidth: 8, usePointStyle: true, font: { size: 11 } }
                                }
                            }
                        }
                    });
                } catch (e) {
                    console.error('Error creating status chart:', e);
                }
            } else {
                sCtx.parentElement.innerHTML = '<div class="db-empty">No booking statistics available.</div>';
            }
        }
    }

    initCharts();
});

</script>
@endpush

@endsection
