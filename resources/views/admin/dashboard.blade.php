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
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg>
                Quick View
            </a>
        </div>
    </div>

    {{-- ══ ALERTS ══ --}}
    @if($lateBookings->count() || $pendingOldBookings->count())
    <div class="db-alerts">
        @if($lateBookings->count())
        <div class="db-alert db-alert--red">
            <div class="db-alert-stripe"></div>
            <div class="db-alert-icon-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <div class="db-alert-content">
                <div class="db-alert-head">
                    Late Returns
                    <span class="db-alert-pill db-alert-pill--red">{{ $lateBookings->count() }}</span>
                </div>
                <div class="db-alert-rows">
                    @foreach($lateBookings->take(3) as $b)
                    <div class="db-alert-row">
                        <span class="db-alert-id">#{{ str_pad($b->id, 5, '0', STR_PAD_LEFT) }}</span>
                        <span class="db-alert-tag">+{{ round($b->late_minutes / 60, 1) }}h late</span>
                        <span class="db-alert-fee">{{ number_format($b->late_fee, 0) }} MAD</span>
                        <a href="{{ route('admin.bookings.show', $b) }}" class="db-alert-cta">View</a>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        @if($pendingOldBookings->count())
        <div class="db-alert db-alert--amber">
            <div class="db-alert-stripe db-alert-stripe--amber"></div>
            <div class="db-alert-icon-wrap db-alert-icon-wrap--amber">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="db-alert-content">
                <div class="db-alert-head">
                    Pending &gt; 24h
                    <span class="db-alert-pill db-alert-pill--amber">{{ $pendingOldBookings->count() }}</span>
                </div>
                <div class="db-alert-rows">
                    @foreach($pendingOldBookings->take(3) as $b)
                    <div class="db-alert-row">
                        <span class="db-alert-id">#{{ str_pad($b->id, 5, '0', STR_PAD_LEFT) }}</span>
                        <span class="db-alert-tag">{{ $b->created_at->diffForHumans() }}</span>
                        <a href="{{ route('admin.bookings.show', $b) }}" class="db-alert-cta db-alert-cta--amber">Review</a>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- ══ KPI GRID ══ --}}
    <div class="db-kpis">
        <div class="db-kpi" style="--accent:#3b82f6">
            <div class="db-kpi-glow" style="--glow:rgba(59,130,246,0.12)"></div>
            <div class="db-kpi-head">
                <div class="db-kpi-icon" style="--ic-bg:rgba(59,130,246,0.12);--ic-c:#3b82f6">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/></svg>
                </div>
                <span class="db-kpi-chip" style="--chip-bg:rgba(16,185,129,0.1);--chip-c:#10b981">{{ $availableCars }} avail</span>
            </div>
            <div class="db-kpi-num">{{ $totalCars }}</div>
            <div class="db-kpi-label">Total Cars</div>
            <div class="db-kpi-track"><div class="db-kpi-fill" style="width:{{ $totalCars > 0 ? ($availableCars/$totalCars)*100 : 0 }}%;background:var(--accent)"></div></div>
        </div>

        <div class="db-kpi" style="--accent:#8b5cf6">
            <div class="db-kpi-glow" style="--glow:rgba(139,92,246,0.1)"></div>
            <div class="db-kpi-head">
                <div class="db-kpi-icon" style="--ic-bg:rgba(139,92,246,0.12);--ic-c:#8b5cf6">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <span class="db-kpi-chip" style="--chip-bg:rgba(245,158,11,0.1);--chip-c:#f59e0b">{{ $pendingBookings }} pending</span>
            </div>
            <div class="db-kpi-num">{{ $totalBookings }}</div>
            <div class="db-kpi-label">Total Bookings</div>
            <div class="db-kpi-track"><div class="db-kpi-fill" style="width:{{ $totalBookings > 0 ? min(100, ($pendingBookings / max($totalBookings,1))*100*5) : 0 }}%;background:var(--accent)"></div></div>
        </div>

        <div class="db-kpi" style="--accent:#6366f1">
            <div class="db-kpi-glow" style="--glow:rgba(99,102,241,0.1)"></div>
            <div class="db-kpi-head">
                <div class="db-kpi-icon" style="--ic-bg:rgba(99,102,241,0.12);--ic-c:#6366f1">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <span class="db-kpi-chip" style="--chip-bg:rgba(99,102,241,0.1);--chip-c:#818cf8">{{ $repeatCustomers ?? 0 }} repeat</span>
            </div>
            <div class="db-kpi-num">{{ $totalUsers }}</div>
            <div class="db-kpi-label">Total Users</div>
            <div class="db-kpi-track"><div class="db-kpi-fill" style="width:60%;background:var(--accent)"></div></div>
        </div>

        <div class="db-kpi" style="--accent:#10b981">
            <div class="db-kpi-glow" style="--glow:rgba(16,185,129,0.1)"></div>
            <div class="db-kpi-head">
                <div class="db-kpi-icon" style="--ic-bg:rgba(16,185,129,0.12);--ic-c:#10b981">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="db-kpi-chip" style="--chip-bg:rgba(16,185,129,0.1);--chip-c:#10b981">All time</span>
            </div>
            <div class="db-kpi-num">{{ number_format($totalRevenue, 0) }}</div>
            <div class="db-kpi-label">Revenue (MAD)</div>
            <div class="db-kpi-track"><div class="db-kpi-fill" style="width:75%;background:var(--accent)"></div></div>
        </div>

        <div class="db-kpi db-kpi--gold" style="--accent:#C89D66">
            <div class="db-kpi-glow" style="--glow:rgba(200,157,102,0.12)"></div>
            <div class="db-kpi-head">
                <div class="db-kpi-icon" style="--ic-bg:rgba(200,157,102,0.12);--ic-c:#C89D66">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
                <span class="db-kpi-chip" style="--chip-bg:rgba(200,157,102,0.1);--chip-c:#C89D66">{{ now()->format('M') }}</span>
            </div>
            <div class="db-kpi-num db-kpi-num--gold">{{ number_format($monthlyRevenue, 0) }}</div>
            <div class="db-kpi-label">This Month (MAD)</div>
            <div class="db-kpi-track"><div class="db-kpi-fill" style="width:55%;background:var(--accent)"></div></div>
        </div>
    </div>

    {{-- ══ SECONDARY ROW ══ --}}
    @if(isset($totalEmails) || isset($totalDamages) || isset($totalReviews) || isset($todayBookings))
    <div class="db-sec-row">
        @if(isset($todayBookings))
        <div class="db-sec">
            <div class="db-sec-ico" style="--c:#a78bfa">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/></svg>
            </div>
            <div class="db-sec-body">
                <div class="db-sec-val">{{ $todayBookings }}</div>
                <div class="db-sec-lbl">Today's Bookings</div>
                <div class="db-sec-sub">{{ number_format($todayRevenue ?? 0, 0) }} MAD</div>
            </div>
        </div>
        @endif
        @if(isset($totalReviews))
        <div class="db-sec">
            <div class="db-sec-ico" style="--c:#fbbf24">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
            </div>
            <div class="db-sec-body">
                <div class="db-sec-val">{{ $totalReviews }}</div>
                <div class="db-sec-lbl">Reviews</div>
                <div class="db-sec-sub">{{ number_format($averageRating, 1) }}★ · {{ $pendingReviews }} pending</div>
            </div>
        </div>
        @endif
        @if(isset($totalDamages))
        <div class="db-sec db-sec--warn">
            <div class="db-sec-ico" style="--c:#f87171">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            </div>
            <div class="db-sec-body">
                <div class="db-sec-val">{{ $totalDamages }}</div>
                <div class="db-sec-lbl">Damages</div>
                <div class="db-sec-sub">{{ $unresolvedDamages }} unresolved</div>
            </div>
        </div>
        @endif
        @if(isset($totalEmails))
        <div class="db-sec">
            <div class="db-sec-ico" style="--c:#38bdf8">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/></svg>
            </div>
            <div class="db-sec-body">
                <div class="db-sec-val">{{ $totalEmails }}</div>
                <div class="db-sec-lbl">Emails Sent</div>
                <div class="db-sec-sub">{{ $failedEmails }} failed · {{ $todayEmails }} today</div>
            </div>
        </div>
        @endif
        @if($endingTodayBookings->count())
        <div class="db-sec db-sec--blue">
            <div class="db-sec-ico" style="--c:#60a5fa">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
            </div>
            <div class="db-sec-body">
                <div class="db-sec-val">{{ $endingTodayBookings->count() }}</div>
                <div class="db-sec-lbl">Ending Today</div>
                <div class="db-sec-sub">
                    @foreach($endingTodayBookings->take(2) as $b)#{{ $b->id }} {{ $b->end_date->format('H:i') }} @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>
    @endif

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
.db-chart-area canvas { width: 100% !important; height: 100% !important; }

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

window.addEventListener('load', function() {
    if (typeof Chart === 'undefined') return;

    Chart.defaults.color = '#52596e';
    Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';
    Chart.defaults.font.family = 'DM Sans, system-ui, sans-serif';
    Chart.defaults.font.size = 11;

    const rCtx = document.getElementById('revenueChart');
    if (rCtx) {
        new Chart(rCtx, {
            type: 'bar',
            data: {
                labels: @json(collect(range(1,12))->map(fn($m) => \Carbon\Carbon::create()->month($m)->format('M'))),
                datasets: [{
                    label: 'Revenue (MAD)',
                    data: @json(collect(range(1,12))->map(fn($m) => $monthlyChart->get($m,0))),
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
    }

    const sCtx = document.getElementById('bookingStatusChart');
    if (sCtx) {
        new Chart(sCtx, {
            type: 'doughnut',
            data: {
                labels: @json($bookingStatusChart->keys()->map(fn($k) => ucfirst($k))),
                datasets: [{
                    data: @json($bookingStatusChart->values()),
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
    }
});
</script>
@endpush

@endsection