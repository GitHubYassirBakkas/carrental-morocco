@extends('admin.layouts.app')

@section('content')

<div class="ac">

    {{-- ══ HEADER ══ --}}
    <div class="ac-header">
        <div class="ac-header-left">
            <div class="ac-breadcrumb">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
                Fleet Management
            </div>
            <h1 class="ac-title">Cars</h1>
            <p class="ac-sub">{{ $cars->total() }} vehicles in fleet</p>
        </div>
        <a href="{{ route('admin.cars.create') }}" class="ac-add-btn">
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
            Add Car
        </a>
    </div>

    {{-- ══ ALERT ══ --}}
    @if(session('success'))
    <div class="ac-success">
        <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- ══ TABLE ══ --}}
    <div class="ac-card">

        {{-- Toolbar --}}
        <div class="ac-toolbar">
            <div class="ac-toolbar-left">
                <div class="ac-stats">
                    <div class="ac-stat">
                        <span class="ac-stat-dot" style="background:#10b981"></span>
                        {{ $cars->where('is_available', true)->count() }} Available
                    </div>
                    <div class="ac-stat">
                        <span class="ac-stat-dot" style="background:#ef4444"></span>
                        {{ $cars->where('is_available', false)->count() }} Unavailable
                    </div>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="ac-table-wrap">
            <table class="ac-table">
                <thead>
                    <tr>
                        <th style="width:80px">Photo</th>
                        <th>Vehicle</th>
                        <th>Type</th>
                        <th>Price / Day</th>
                        <th>Status</th>
                        <th style="width:130px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cars as $car)
                    <tr class="ac-row">
                        <td>
                            <div class="ac-car-img">
                                @if($car->image)
                                    <img src="{{ asset('storage/cars/'.$car->image) }}" alt="{{ $car->full_name }}">
                                @else
                                    <div class="ac-car-img-ph">
                                        <svg viewBox="0 0 20 20" fill="currentColor"><path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/><path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/></svg>
                                    </div>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="ac-car-name">{{ $car->full_name }}</div>
                            @if($car->location)
                                <div class="ac-car-loc">
                                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                                    {{ $car->location->name }}
                                </div>
                            @endif
                        </td>
                        <td>
                            @if($car->type)
                                <span class="ac-type-pill">{{ ucfirst($car->type) }}</span>
                            @else
                                <span class="ac-dash">—</span>
                            @endif
                        </td>
                        <td>
                            <div class="ac-price">{{ $car->formatted_price }}</div>
                        </td>
                        <td>
                            @if($car->is_available)
                                <div class="ac-status ac-status--on">
                                    <span class="ac-status-dot"></span>
                                    Available
                                </div>
                            @else
                                <div class="ac-status ac-status--off">
                                    <span class="ac-status-dot"></span>
                                    Unavailable
                                </div>
                            @endif
                        </td>
                        <td>
                            <div class="ac-actions">
                                <a href="{{ route('admin.cars.edit', $car) }}" class="ac-btn ac-btn--edit">
                                    <svg viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>
                                </a>
                                <form action="{{ route('admin.cars.destroy', $car) }}" method="POST" class="ac-del-form"
                                      onsubmit="return confirm('Delete {{ $car->full_name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ac-btn ac-btn--del">
                                        <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="ac-empty">
                            <div class="ac-empty-inner">
                                <div class="ac-empty-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/></svg>
                                </div>
                                <p>No cars in fleet yet</p>
                                <a href="{{ route('admin.cars.create') }}" class="ac-empty-btn">Add First Car</a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($cars->hasPages())
        <div class="ac-pagination">
            {{ $cars->links() }}
        </div>
        @endif

    </div>
</div>

<style>
.ac {
    --bg:   #07090f;
    --bg2:  #0c0f18;
    --bg3:  #111520;
    --bdr:  rgba(255,255,255,0.055);
    --txt:  #e8eaf0;
    --muted:#52596e;
    --hint: #2a3045;
    --gold: #C89D66;
    background: var(--bg);
    color: var(--txt);
    font-family: 'DM Sans', system-ui, sans-serif;
    padding: 1.75rem 2rem;
    min-height: 100%;
}

/* ── HEADER ── */
.ac-header {
    display: flex; justify-content: space-between; align-items: flex-start;
    margin-bottom: 1.5rem; gap: 1rem;
}
.ac-breadcrumb {
    display: flex; align-items: center; gap: 6px;
    font-size: 0.65rem; font-weight: 700; color: var(--muted);
    letter-spacing: 0.15em; text-transform: uppercase; margin-bottom: 5px;
}
.ac-breadcrumb svg { width: 12px; height: 12px; }
.ac-title {
    font-size: clamp(1.6rem, 3vw, 2.2rem);
    font-weight: 900; letter-spacing: -0.05em;
    color: var(--txt); line-height: 1; margin-bottom: 3px;
}
.ac-sub { font-size: 0.75rem; color: var(--muted); }

.ac-add-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 9px 18px;
    background: linear-gradient(135deg, var(--gold), #B8935E);
    border-radius: 9px; color: #fff;
    font-size: 0.82rem; font-weight: 700;
    text-decoration: none; white-space: nowrap;
    box-shadow: 0 4px 16px rgba(200,157,102,0.25);
    transition: opacity 0.2s, box-shadow 0.2s;
}
.ac-add-btn svg { width: 15px; height: 15px; }
.ac-add-btn:hover { opacity: 0.9; box-shadow: 0 6px 22px rgba(200,157,102,0.4); }

/* ── SUCCESS ── */
.ac-success {
    display: flex; align-items: center; gap: 10px;
    background: rgba(16,185,129,0.08);
    border: 1px solid rgba(16,185,129,0.2);
    border-radius: 9px; padding: 11px 15px;
    font-size: 0.82rem; color: #10b981;
    margin-bottom: 1.25rem;
}
.ac-success svg { width: 16px; height: 16px; flex-shrink: 0; }

/* ── CARD ── */
.ac-card {
    background: var(--bg2);
    border: 1px solid var(--bdr);
    border-radius: 14px; overflow: hidden;
    animation: ac-up 0.35s ease both;
}
@keyframes ac-up { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:none} }

/* ── TOOLBAR ── */
.ac-toolbar {
    display: flex; justify-content: space-between; align-items: center;
    padding: 0.875rem 1.25rem;
    border-bottom: 1px solid var(--bdr);
    gap: 1rem;
}
.ac-stats { display: flex; gap: 16px; }
.ac-stat {
    display: flex; align-items: center; gap: 6px;
    font-size: 0.75rem; color: var(--muted);
}
.ac-stat-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }

/* ── TABLE ── */
.ac-table-wrap { overflow-x: auto; }
.ac-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }

.ac-table thead th {
    padding: 9px 16px;
    background: rgba(255,255,255,0.02);
    color: var(--muted); font-size: 0.62rem;
    font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em;
    border-bottom: 1px solid var(--bdr);
    text-align: left; white-space: nowrap;
}

.ac-row { transition: background 0.12s; }
.ac-row:hover { background: rgba(255,255,255,0.02); }
.ac-table tbody td {
    padding: 12px 16px; color: var(--txt);
    border-bottom: 1px solid rgba(255,255,255,0.025);
    vertical-align: middle;
}
.ac-row:last-child td { border-bottom: none; }

/* Car image */
.ac-car-img {
    width: 72px; height: 46px; border-radius: 7px;
    overflow: hidden; background: var(--bg3);
    border: 1px solid var(--bdr);
}
.ac-car-img img { width: 100%; height: 100%; object-fit: cover; }
.ac-car-img-ph {
    width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center;
    color: var(--hint);
}
.ac-car-img-ph svg { width: 20px; height: 20px; }

/* Car name */
.ac-car-name { font-weight: 700; color: var(--txt); margin-bottom: 3px; letter-spacing: -0.01em; }
.ac-car-loc {
    display: flex; align-items: center; gap: 4px;
    font-size: 0.7rem; color: var(--muted);
}
.ac-car-loc svg { width: 10px; height: 10px; color: var(--gold); flex-shrink: 0; }

/* Type pill */
.ac-type-pill {
    display: inline-block; padding: 2px 9px;
    background: rgba(255,255,255,0.05);
    border: 1px solid var(--bdr);
    border-radius: 100px; font-size: 0.68rem;
    font-weight: 600; color: var(--muted);
    letter-spacing: 0.04em;
}

/* Price */
.ac-price { font-weight: 700; color: var(--gold); font-size: 0.9rem; letter-spacing: -0.02em; }

/* Status */
.ac-status {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 3px 10px; border-radius: 100px;
    font-size: 0.7rem; font-weight: 700;
}
.ac-status-dot { width: 5px; height: 5px; border-radius: 50%; }

.ac-status--on {
    background: rgba(16,185,129,0.1);
    border: 1px solid rgba(16,185,129,0.2);
    color: #10b981;
}
.ac-status--on .ac-status-dot {
    background: #10b981;
    animation: ac-pulse 2s ease infinite;
}
@keyframes ac-pulse { 0%,100%{opacity:1} 50%{opacity:.4} }

.ac-status--off {
    background: rgba(239,68,68,0.08);
    border: 1px solid rgba(239,68,68,0.15);
    color: #f87171;
}
.ac-status--off .ac-status-dot { background: #f87171; }

.ac-dash { color: var(--hint); }

/* Actions */
.ac-actions { display: flex; align-items: center; gap: 6px; }
.ac-del-form { display: inline; }

.ac-btn {
    width: 32px; height: 32px; border-radius: 7px;
    display: inline-flex; align-items: center; justify-content: center;
    border: none; cursor: pointer; transition: all 0.15s;
    text-decoration: none;
}
.ac-btn svg { width: 14px; height: 14px; }

.ac-btn--edit {
    background: rgba(200,157,102,0.08);
    border: 1px solid rgba(200,157,102,0.15);
    color: var(--gold);
}
.ac-btn--edit:hover { background: rgba(200,157,102,0.18); border-color: rgba(200,157,102,0.35); }

.ac-btn--del {
    background: rgba(239,68,68,0.08);
    border: 1px solid rgba(239,68,68,0.12);
    color: #f87171;
}
.ac-btn--del:hover { background: rgba(239,68,68,0.18); border-color: rgba(239,68,68,0.3); }

/* Empty */
.ac-empty { padding: 0 !important; border: none !important; }
.ac-empty-inner {
    display: flex; flex-direction: column; align-items: center;
    padding: 4rem 2rem; gap: 10px;
}
.ac-empty-icon {
    width: 52px; height: 52px; border-radius: 12px;
    background: rgba(200,157,102,0.08); border: 1px solid rgba(200,157,102,0.15);
    display: flex; align-items: center; justify-content: center; color: var(--gold); opacity: .6;
}
.ac-empty-icon svg { width: 24px; height: 24px; }
.ac-empty-inner p { font-size: 0.85rem; color: var(--muted); }
.ac-empty-btn {
    padding: 8px 20px; background: linear-gradient(135deg,var(--gold),#B8935E);
    border-radius: 8px; color: #fff; font-size: 0.8rem; font-weight: 700;
    text-decoration: none; transition: opacity .2s;
}
.ac-empty-btn:hover { opacity: .9; }

/* Pagination */
.ac-pagination {
    padding: 1rem 1.25rem;
    border-top: 1px solid var(--bdr);
}
/* Override Laravel pagination */
.ac-pagination nav { display: flex; }
.ac-pagination .pagination { display: flex; gap: 4px; list-style: none; }
.ac-pagination .page-item .page-link {
    display: flex; align-items: center; justify-content: center;
    min-width: 32px; height: 32px; padding: 0 8px;
    border-radius: 7px;
    background: var(--bg3); border: 1px solid var(--bdr);
    color: var(--muted); font-size: 0.78rem; text-decoration: none;
    transition: all 0.15s;
}
.ac-pagination .page-item.active .page-link {
    background: var(--gold); border-color: var(--gold); color: #fff;
}
.ac-pagination .page-item .page-link:hover { border-color: var(--gold); color: var(--gold); }
.ac-pagination .page-item.disabled .page-link { opacity: .35; cursor: not-allowed; }
</style>

@endsection