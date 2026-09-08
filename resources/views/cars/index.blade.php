@extends('layouts.app')

@section('content')

{{-- ═══════════════════════════════════════════
     HERO
═══════════════════════════════════════════ --}}
<div class="cars-hero">
    <div class="cars-hero-overlay"></div>
    <div class="cars-hero-bg" style="background-image: url('https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=1600')"></div>
    <div class="cars-hero-content">
        <div class="hero-badge">
            <span></span>{{ __('messages.available_cars') }}
        </div>
        <h1 class="hero-title">{{ __('messages.hero_title') }}</h1>
        <p class="hero-sub">{{ __('messages.hero_subtitle') }}</p>
 
        {{-- Quick stats --}}
        <div class="hero-stats">
            <div class="hstat"><strong>{{ $cars->total() }}</strong><span>{{ __('messages.results_found') }}</span></div>
            <div class="hstat-div"></div>
            <div class="hstat"><strong>500+</strong><span>{{ __('messages.stat_cars') }}</span></div>
            <div class="hstat-div"></div>
            <div class="hstat"><strong>24/7</strong><span>{{ __('messages.stat_support') }}</span></div>
        </div>
    </div>
</div>
 
{{-- ═══════════════════════════════════════════
     MAIN LAYOUT
═══════════════════════════════════════════ --}}
<div class="cars-layout">
 
    {{-- ─────────── SIDEBAR ─────────── --}}
    <aside class="cars-sidebar" id="sidebar">
        <div class="sidebar-inner">
 
            <div class="sidebar-header">
                <h2>{{ __('messages.filter') }}</h2>
                @if(request()->hasAny(['search','location','brand','type','transmission','min_price','max_price']))
                    <a href="{{ route('cars.index') }}" class="clear-all">{{ __('messages.clear_filters') }}</a>
                @endif
            </div>
 
            <form action="{{ route('cars.index') }}" method="GET" id="filterForm">
 
                {{-- Search --}}
                <div class="filter-group">
                    <label>{{ __('messages.search') }}</label>
                    <div class="filter-input-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="{{ __('messages.search_placeholder') }}">
                    </div>
                </div>
 
                {{-- Location --}}
                <div class="filter-group">
                    <label>{{ __('messages.pickup_location') }}</label>
                    <div class="filter-select-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <select name="location">
                            <option value="">{{ __('messages.select_location') }}</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" {{ request('location') == $location->id ? 'selected' : '' }}>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
 
                {{-- Brand --}}
                <div class="filter-group">
                    <label>{{ __('messages.all_brands') }}</label>
                    <div class="filter-select-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        <select name="brand">
                            <option value="">{{ __('messages.all_brands') }}</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand }}" {{ request('brand') == $brand ? 'selected' : '' }}>{{ $brand }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
 
                {{-- Type checkboxes --}}
                <div class="filter-group">
                    <label>{{ __('messages.all_types') }}</label>
                    <div class="type-grid">
                        @foreach($types as $type)
                            @php $isActive = in_array($type, (array) request('type', [])); @endphp
                            <label class="type-chip {{ $isActive ? 'type-chip--active' : '' }}">
                                <input type="checkbox" name="type[]" value="{{ $type }}" {{ $isActive ? 'checked' : '' }} class="hidden" onchange="document.getElementById('filterForm').submit()">
                                <span>{{ $type }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
 
                {{-- Transmission --}}
                <div class="filter-group">
                    <label>{{ __('messages.transmission') }}</label>
                    <div class="trans-btns">
                        <label class="trans-btn {{ request('transmission') == '' ? 'trans-active' : '' }}">
                            <input type="radio" name="transmission" value="" {{ request('transmission') == '' ? 'checked' : '' }} class="hidden">
                            {{ __('messages.all_types') }}
                        </label>
                        <label class="trans-btn {{ request('transmission') == 'Automatic' ? 'trans-active' : '' }}">
                            <input type="radio" name="transmission" value="Automatic" {{ request('transmission') == 'Automatic' ? 'checked' : '' }} class="hidden">
                            {{ __('messages.automatic') }}
                        </label>
                        <label class="trans-btn {{ request('transmission') == 'Manual' ? 'trans-active' : '' }}">
                            <input type="radio" name="transmission" value="Manual" {{ request('transmission') == 'Manual' ? 'checked' : '' }} class="hidden">
                            {{ __('messages.manual') }}
                        </label>
                    </div>
                </div>
 
                {{-- Price Range --}}
                <div class="filter-group">
                    <label>{{ __('messages.price_range') }} <span class="price-label" id="priceLabel"></span></label>
                    <div class="price-inputs">
                        <div class="price-field">
                            <span>{{ __('messages.min_price') }}</span>
                            <input type="number" name="min_price" id="minPrice"
                                   value="{{ request('min_price') }}"
                                   placeholder="{{ $minPrice }}">
                        </div>
                        <div class="price-sep">—</div>
                        <div class="price-field">
                            <span>{{ __('messages.max_price') }}</span>
                            <input type="number" name="max_price" id="maxPrice"
                                   value="{{ request('max_price') }}"
                                   placeholder="{{ $maxPrice }}">
                        </div>
                    </div>
                    <p class="price-unit">MAD / {{ __('messages.per_day') }}</p>
                </div>
 
                <button type="submit" class="btn-apply">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"/></svg>
                    {{ __('messages.apply_filters') }}
                </button>
 
            </form>
        </div>
    </aside>
 
    {{-- ─────────── CARS GRID ─────────── --}}
    <main class="cars-main">
 
        {{-- Top bar --}}
        <div class="cars-topbar">
            <p class="results-count">
                <strong>{{ $cars->total() }}</strong> {{ __('messages.results_found') }}
            </p>
            <button class="mobile-filter-btn" onclick="document.getElementById('sidebar').classList.toggle('sidebar--open')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                </svg>
                {{ __('messages.filter') }}
            </button>
        </div>
 
        @if($cars->count() > 0)
 
            <div class="cars-grid" id="carsGrid">
                @foreach($cars as $i => $car)
                    <article class="car-card" style="animation-delay: {{ $i * 0.06 }}s">
 
                        {{-- Image --}}
                        <div class="car-img-wrap">
                            <img
                                src="{{ $car->image ? asset('storage/cars/' . $car->image) : asset('images/no-car.png') }}"
                                alt="{{ $car->brand }} {{ $car->model }}"
                                loading="lazy"
                                onerror="this.src='https://via.placeholder.com/400x260/111/C89D66?text={{ urlencode($car->brand . ' ' . $car->model) }}'">
 
                            <div class="car-badge">{{ $car->type }}</div>
 
                            {{-- Hover overlay --}}
                            <div class="car-img-overlay">
                                <a href="{{ route('insurance.select', $car) }}" class="overlay-btn">
                                    {{ __('messages.view_details') }}
                                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                </a>
                            </div>
                        </div>
 
                        {{-- Body --}}
                        <div class="car-body">
                            <div class="car-header">
                                <h3 class="car-title">{{ $car->brand }} {{ $car->model }}</h3>
                                @if($car->location)
                                    <p class="car-loc">
                                        <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                                        {{ $car->location->name }}
                                    </p>
                                @endif
                            </div>
 
                            {{-- Specs --}}
                            <div class="car-specs">
                                <div class="spec">
                                    <svg viewBox="0 0 20 20" fill="currentColor"><path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/></svg>
                                    <span>{{ $car->seats }} {{ __('messages.seats') }}</span>
                                </div>
                                <div class="spec">
                                    <svg viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a1 1 0 011 1v1.323l3.954 1.582 1.599-.8a1 1 0 01.894 1.79l-1.233.616 1.738 5.42a1 1 0 01-.285 1.05A3.989 3.989 0 0115 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.715-5.349L11 6.477V16h2a1 1 0 110 2H7a1 1 0 110-2h2V6.477L6.237 7.582l1.715 5.349a1 1 0 01-.285 1.05A3.989 3.989 0 015 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.738-5.42-1.233-.617a1 1 0 01.894-1.788l1.599.799L9 4.323V3a1 1 0 011-1z"/></svg>
                                    <span>{{ $car->transmission }}</span>
                                </div>
                                <div class="spec">
                                    <svg viewBox="0 0 20 20" fill="currentColor"><path d="M8 2a2 2 0 00-2 2v1H5a3 3 0 00-3 3v9a2 2 0 002 2h12a2 2 0 002-2V8a3 3 0 00-3-3h-1V4a2 2 0 00-2-2H8zm0 2h4v1H8V4z"/></svg>
                                    <span>{{ $car->luggage }} {{ __('messages.doors') }}</span>
                                </div>
                            </div>
 
                            {{-- Footer --}}
                            <div class="car-footer">
                                <div class="car-price">
                                    <span class="price-from">{{ __('messages.starting_from') }}</span>
                                    <strong class="price-num">{{ number_format($car->price_per_day, 0) }}</strong>
                                    <span class="price-curr">MAD<em>/{{ __('messages.per_day') }}</em></span>
                                </div>
                                <a href="{{ route('insurance.select', $car) }}" class="btn-details">
                                    {{ __('messages.view_details') }}
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
 
            {{-- Pagination --}}
            <div class="pagination-wrap">
                @if ($cars->onFirstPage())
                    <span class="pg-btn pg-btn--disabled">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </span>
                @else
                    <a href="{{ $cars->previousPageUrl() }}" class="pg-btn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </a>
                @endif
 
                @foreach ($cars->getUrlRange(1, $cars->lastPage()) as $page => $url)
                    @if ($page == $cars->currentPage())
                        <span class="pg-btn pg-btn--active">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="pg-btn">{{ $page }}</a>
                    @endif
                @endforeach
 
                @if ($cars->hasMorePages())
                    <a href="{{ $cars->nextPageUrl() }}" class="pg-btn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                @else
                    <span class="pg-btn pg-btn--disabled">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </span>
                @endif
            </div>
 
        @else
            {{-- Empty state --}}
            <div class="empty-state">
                <div class="empty-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3>{{ __('messages.no_results') }}</h3>
                <p>{{ __('messages.no_results_desc') }}</p>
                <a href="{{ route('cars.index') }}" class="btn-apply" style="display:inline-flex;width:auto;padding:0.75rem 2rem">
                    {{ __('messages.clear_filters') }}
                </a>
            </div>
        @endif
 
    </main>
</div>
 
{{-- ═══════════════════════════════════════════
     STYLES
═══════════════════════════════════════════ --}}
<style>
:root {
    --gold:      #C89D66;
    --gold-dark: #B8935E;
    --gold-glow: rgba(200,157,102,0.15);
    --bg:        #0a0a0a;
    --bg-card:   #0f0f0f;
    --bg-input:  #141414;
    --border:    #1e1e1e;
    --border-2:  #2a2a2a;
    --text:      #f0f0f0;
    --muted:     #888;
    --hint:      #444;
    --radius:    12px;
    --tr:        0.2s ease;
}
 
/* ── Hero ── */
.cars-hero {
    position: relative;
    height: 320px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
 
.cars-hero-bg {
    position: absolute; inset: 0;
    background-size: cover;
    background-position: center;
    transform: scale(1.05);
    transition: transform 8s ease;
}
 
.cars-hero:hover .cars-hero-bg { transform: scale(1); }
 
.cars-hero-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(to bottom, rgba(10,10,10,0.7) 0%, rgba(10,10,10,0.85) 100%);
    z-index: 1;
}
 
.cars-hero-content {
    position: relative;
    z-index: 2;
    text-align: center;
    padding: 0 1rem;
    animation: fadeUp 0.6s ease both;
}
 
@keyframes fadeUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:none; } }
 
.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(200,157,102,0.12);
    border: 1px solid rgba(200,157,102,0.25);
    border-radius: 100px;
    padding: 5px 16px;
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--gold);
    letter-spacing: 0.1em;
    text-transform: uppercase;
    margin-bottom: 1rem;
}
 
.hero-badge span {
    width: 6px; height: 6px;
    border-radius: 50%;
    background: var(--gold);
    animation: pulse 2s ease infinite;
    display: inline-block;
}
 
@keyframes pulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:0.4;transform:scale(0.8)} }
 
.hero-title {
    font-size: clamp(2rem, 5vw, 3.5rem);
    font-weight: 800;
    color: #fff;
    letter-spacing: -0.04em;
    line-height: 1.1;
    margin-bottom: 0.6rem;
}
 
.hero-sub {
    font-size: 1rem;
    color: #aaa;
    margin-bottom: 1.75rem;
}
 
.hero-stats {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1.5rem;
}
 
.hstat { text-align: center; }
.hstat strong { display: block; font-size: 1.4rem; font-weight: 700; color: var(--gold); }
.hstat span   { font-size: 0.65rem; color: #666; text-transform: uppercase; letter-spacing: 0.06em; }
.hstat-div    { width: 1px; height: 30px; background: #333; }
 
/* ── Layout ── */
.cars-layout {
    display: flex;
    gap: 0;
    min-height: calc(100vh - 320px);
    background: var(--bg);
}
 
/* ── Sidebar ── */
.cars-sidebar {
    width: 280px;
    flex-shrink: 0;
    border-right: 1px solid var(--border);
    background: #0c0c0c;
    transition: transform var(--tr);
}
 
.sidebar-inner {
    position: sticky;
    top: 80px;
    padding: 1.5rem;
    max-height: calc(100vh - 80px);
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: #2a2a2a transparent;
}
 
.sidebar-inner::-webkit-scrollbar { width: 4px; }
.sidebar-inner::-webkit-scrollbar-thumb { background: #2a2a2a; border-radius: 4px; }
 
.sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border);
}
 
.sidebar-header h2 {
    font-size: 1rem;
    font-weight: 700;
    color: var(--text);
    letter-spacing: -0.01em;
}
 
.clear-all {
    font-size: 0.72rem;
    color: var(--gold);
    text-decoration: none;
    font-weight: 600;
    transition: color var(--tr);
}
 
.clear-all:hover { color: #fff; }
 
/* Filter groups */
.filter-group { margin-bottom: 1.25rem; }
 
.filter-group > label {
    display: block;
    font-size: 0.68rem;
    font-weight: 700;
    color: var(--muted);
    letter-spacing: 0.08em;
    text-transform: uppercase;
    margin-bottom: 0.5rem;
}
 
.filter-input-wrap,
.filter-select-wrap {
    position: relative;
}
 
.filter-input-wrap svg,
.filter-select-wrap svg {
    position: absolute;
    left: 11px; top: 50%;
    transform: translateY(-50%);
    width: 15px; height: 15px;
    color: var(--hint);
    pointer-events: none;
}
 
.filter-input-wrap input,
.filter-select-wrap select {
    width: 100% !important;
    background: var(--bg-input) !important;
    border: 1px solid var(--border-2) !important;
    border-radius: 8px !important;
    color: var(--text) !important;
    font-size: 0.85rem !important;
    padding: 0.65rem 0.75rem 0.65rem 2.25rem !important;
    outline: none !important;
    -webkit-text-fill-color: var(--text) !important;
    transition: border-color var(--tr);
    appearance: none;
    -webkit-appearance: none;
}
 
.filter-input-wrap input:-webkit-autofill,
.filter-select-wrap select:-webkit-autofill {
    -webkit-box-shadow: 0 0 0 1000px var(--bg-input) inset !important;
    -webkit-text-fill-color: var(--text) !important;
}
 
.filter-input-wrap input::placeholder { color: var(--hint) !important; }
 
.filter-input-wrap input:focus,
.filter-select-wrap select:focus {
    border-color: var(--gold) !important;
    box-shadow: 0 0 0 2px var(--gold-glow) !important;
}
 
.filter-select-wrap::after {
    content: '';
    position: absolute;
    right: 11px; top: 50%;
    transform: translateY(-50%);
    width: 0; height: 0;
    border-left: 4px solid transparent;
    border-right: 4px solid transparent;
    border-top: 5px solid var(--hint);
    pointer-events: none;
}
 
/* Type chips */
.type-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
 
.type-chip {
    padding: 5px 12px;
    border-radius: 100px;
    border: 1px solid var(--border-2);
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--muted);
    background: transparent;
    cursor: pointer;
    transition: all var(--tr);
    user-select: none;
}
 
.type-chip:hover { border-color: var(--gold); color: var(--gold); }
 
.type-chip--active {
    background: rgba(200,157,102,0.12);
    border-color: rgba(200,157,102,0.4);
    color: var(--gold);
}
 
/* Transmission radio */
.trans-btns { display: flex; gap: 6px; }
 
.trans-btn {
    flex: 1;
    text-align: center;
    padding: 6px 4px;
    border-radius: 8px;
    border: 1px solid var(--border-2);
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--muted);
    cursor: pointer;
    transition: all var(--tr);
    user-select: none;
}
 
.trans-btn:hover { border-color: var(--gold); color: var(--gold); }
.trans-active { background: rgba(200,157,102,0.12); border-color: rgba(200,157,102,0.4) !important; color: var(--gold) !important; }
 
/* Price */
.price-inputs {
    display: flex;
    align-items: center;
    gap: 8px;
}
 
.price-field {
    flex: 1;
    background: var(--bg-input);
    border: 1px solid var(--border-2);
    border-radius: 8px;
    padding: 6px 10px;
    transition: border-color var(--tr);
}
 
.price-field:focus-within { border-color: var(--gold); }
 
.price-field span {
    display: block;
    font-size: 0.6rem;
    color: var(--hint);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 2px;
}
 
.price-field input {
    width: 100% !important;
    background: transparent !important;
    border: none !important;
    color: var(--text) !important;
    font-size: 0.85rem !important;
    font-weight: 600 !important;
    padding: 0 !important;
    outline: none !important;
    -webkit-text-fill-color: var(--text) !important;
}
 
.price-field input::placeholder { color: var(--hint) !important; font-weight: 400 !important; }
.price-sep { color: var(--hint); font-size: 0.85rem; }
.price-unit { font-size: 0.65rem; color: var(--hint); margin-top: 6px; }
.price-label { font-size: 0.7rem; color: var(--gold); font-weight: 600; }
 
/* Apply button */
.btn-apply {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 0.75rem;
    background: linear-gradient(135deg, var(--gold), var(--gold-dark));
    border: none;
    border-radius: 8px;
    color: #fff;
    font-size: 0.875rem;
    font-weight: 700;
    cursor: pointer;
    transition: opacity var(--tr), box-shadow var(--tr);
    box-shadow: 0 4px 16px rgba(200,157,102,0.2);
    text-decoration: none;
    margin-top: 0.25rem;
}
 
.btn-apply svg { width: 15px; height: 15px; }
.btn-apply:hover { opacity: 0.88; box-shadow: 0 6px 24px rgba(200,157,102,0.35); }
 
/* Mobile sidebar */
@media (max-width: 1023px) {
    .cars-sidebar {
        position: fixed;
        top: 0; left: 0;
        height: 100vh;
        z-index: 200;
        transform: translateX(-100%);
        width: 300px;
        box-shadow: 10px 0 40px rgba(0,0,0,0.5);
    }
 
    .cars-sidebar.sidebar--open { transform: translateX(0); }
}
 
/* ── Main ── */
.cars-main { flex: 1; padding: 1.5rem; min-width: 0; }
 
.cars-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border);
}
 
.results-count { font-size: 0.875rem; color: var(--muted); }
.results-count strong { color: var(--text); font-weight: 700; }
 
.mobile-filter-btn {
    display: none;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    background: var(--bg-input);
    border: 1px solid var(--border-2);
    border-radius: 8px;
    color: var(--text);
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: border-color var(--tr);
}
 
.mobile-filter-btn svg { width: 15px; height: 15px; }
.mobile-filter-btn:hover { border-color: var(--gold); color: var(--gold); }
 
@media (max-width: 1023px) { .mobile-filter-btn { display: flex; } }
 
/* ── Car Grid ── */
.cars-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.25rem;
}
 
/* ── Car Card ── */
.car-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    transition: transform var(--tr), border-color var(--tr), box-shadow var(--tr);
    animation: fadeUp 0.5s ease both;
}
 
.car-card:hover {
    transform: translateY(-4px);
    border-color: rgba(200,157,102,0.25);
    box-shadow: 0 16px 40px rgba(0,0,0,0.4), 0 0 0 1px rgba(200,157,102,0.1);
}
 
/* Image */
.car-img-wrap {
    position: relative;
    height: 200px;
    overflow: hidden;
    background: #111;
}
 
.car-img-wrap img {
    width: 100%; height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}
 
.car-card:hover .car-img-wrap img { transform: scale(1.06); }
 
.car-badge {
    position: absolute;
    top: 12px; left: 12px;
    background: var(--gold);
    color: #0a0a0a;
    font-size: 0.65rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    padding: 4px 10px;
    border-radius: 100px;
}
 
.car-img-overlay {
    position: absolute; inset: 0;
    background: rgba(10,10,10,0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity var(--tr);
}
 
.car-card:hover .car-img-overlay { opacity: 1; }
 
.overlay-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 9px 20px;
    background: var(--gold);
    border-radius: 8px;
    color: #0a0a0a;
    font-size: 0.8rem;
    font-weight: 700;
    text-decoration: none;
    transform: translateY(8px);
    transition: transform var(--tr), background var(--tr);
}
 
.car-card:hover .overlay-btn { transform: translateY(0); }
.overlay-btn svg { width: 15px; height: 15px; }
.overlay-btn:hover { background: #d4ab76; }
 
/* Body */
.car-body { padding: 1rem 1.1rem 1.1rem; }
 
.car-header { margin-bottom: 0.75rem; }
 
.car-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--text);
    letter-spacing: -0.02em;
    margin-bottom: 3px;
}
 
.car-loc {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.75rem;
    color: var(--muted);
}
 
.car-loc svg { width: 12px; height: 12px; flex-shrink: 0; }
 
/* Specs */
.car-specs {
    display: flex;
    gap: 0;
    border-top: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
    padding: 0.65rem 0;
    margin-bottom: 0.85rem;
}
 
.spec {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding: 0 4px;
    border-right: 1px solid var(--border);
}
 
.spec:last-child { border-right: none; }
 
.spec svg { width: 15px; height: 15px; color: var(--gold); }
 
.spec span {
    font-size: 0.68rem;
    color: var(--muted);
    text-align: center;
    line-height: 1.2;
}
 
/* Footer */
.car-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}
 
.car-price { line-height: 1; }
 
.price-from {
    display: block;
    font-size: 0.65rem;
    color: var(--hint);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 3px;
}
 
.price-num {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--gold);
    letter-spacing: -0.03em;
}
 
.price-curr {
    font-size: 0.72rem;
    color: var(--muted);
    font-weight: 600;
}
 
.price-curr em {
    font-style: normal;
    font-weight: 400;
    color: var(--hint);
}
 
.btn-details {
    padding: 8px 16px;
    background: transparent;
    border: 1px solid var(--border-2);
    border-radius: 8px;
    color: var(--text);
    font-size: 0.78rem;
    font-weight: 600;
    text-decoration: none;
    white-space: nowrap;
    transition: all var(--tr);
    flex-shrink: 0;
}
 
.btn-details:hover {
    border-color: var(--gold);
    color: var(--gold);
    background: var(--gold-glow);
}
 
/* ── Pagination ── */
.pagination-wrap {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 6px;
    margin-top: 2.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border);
}
 
.pg-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 38px;
    height: 38px;
    padding: 0 10px;
    border-radius: 8px;
    background: var(--bg-input);
    border: 1px solid var(--border-2);
    color: var(--text);
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: none;
    transition: all var(--tr);
}
 
.pg-btn svg { width: 16px; height: 16px; }
.pg-btn:hover { border-color: var(--gold); color: var(--gold); }
.pg-btn--active { background: var(--gold); border-color: var(--gold); color: #0a0a0a; }
.pg-btn--disabled { opacity: 0.3; cursor: not-allowed; pointer-events: none; }
 
/* ── Empty state ── */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 5rem 2rem;
    text-align: center;
    gap: 0.75rem;
}
 
.empty-icon {
    width: 80px; height: 80px;
    background: rgba(200,157,102,0.08);
    border: 1px solid rgba(200,157,102,0.15);
    border-radius: 20px;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 0.5rem;
}
 
.empty-icon svg { width: 36px; height: 36px; color: var(--gold); opacity: 0.6; }
.empty-state h3 { font-size: 1.25rem; font-weight: 700; color: var(--text); }
.empty-state p  { font-size: 0.875rem; color: var(--muted); margin-bottom: 0.5rem; }
 
/* RTL */
[dir="rtl"] .cars-sidebar { border-right: none; border-left: 1px solid var(--border); }
[dir="rtl"] .filter-input-wrap svg,
[dir="rtl"] .filter-select-wrap svg { left: auto; right: 11px; }
[dir="rtl"] .filter-input-wrap input,
[dir="rtl"] .filter-select-wrap select { padding: 0.65rem 2.25rem 0.65rem 0.75rem !important; }
[dir="rtl"] .filter-select-wrap::after { right: auto; left: 11px; }
[dir="rtl"] .car-badge { left: auto; right: 12px; }
[dir="rtl"] .spec { border-right: none; border-left: 1px solid var(--border); }
[dir="rtl"] .spec:last-child { border-left: none; }
[dir="rtl"] .sidebar--open { transform: translateX(100%); right: 0; left: auto; }
 
@media (max-width: 1023px) {
    [dir="rtl"] .cars-sidebar { left: auto; right: 0; transform: translateX(100%); }
    [dir="rtl"] .cars-sidebar.sidebar--open { transform: translateX(0); }
}
</style>
 
<script>
// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(e) {
    const sidebar = document.getElementById('sidebar');
    if (sidebar.classList.contains('sidebar--open') &&
        !sidebar.contains(e.target) &&
        !e.target.closest('.mobile-filter-btn')) {
        sidebar.classList.remove('sidebar--open');
    }
});
 
// Auto-submit on transmission radio change
document.querySelectorAll('input[name="transmission"]').forEach(r => {
    r.addEventListener('change', () => document.getElementById('filterForm').submit());
});
</script>
 
@endsection