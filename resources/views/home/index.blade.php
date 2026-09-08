@extends('layouts.app')

@section('title', __('messages.welcome') . ' — CarRental Morocco')

@section('content')

{{-- ═══════════════════════════════════════
     HERO — Carousel
═══════════════════════════════════════ --}}
<section class="home-hero">

    {{-- Carousel --}}
    <div class="hero-carousel">
        <div class="hero-slide active" style="background-image:url('{{ asset('images/hero/Mercedes.jpg') }}')"></div>
        <div class="hero-slide" style="background-image:url('{{ asset('images/hero/Porsche.jpg') }}')"></div>
        <div class="hero-slide" style="background-image:url('{{ asset('images/hero/BMW.jpg') }}')"></div>
        <div class="hero-overlay"></div>
        <div class="hero-progress-bar" id="heroProgress"></div>
        <div class="hero-dots" id="heroDots">
            <span class="hero-dot hero-dot--active" data-i="0"></span>
            <span class="hero-dot" data-i="1"></span>
            <span class="hero-dot" data-i="2"></span>
        </div>
    </div>

    {{-- Content --}}
    <div class="hero-content">
        <div class="hero-text">
            <span class="hero-eyebrow">{{ __('messages.home_hero_eyebrow') }}</span>
            <h1 class="hero-title" id="heroTitle">{{ __('messages.hero_title') }}</h1>
            <p class="hero-sub" id="heroSub">{{ __('messages.hero_subtitle') }}</p>
        </div>

        {{-- Search form --}}
        <form action="{{ route('cars.search') }}" method="POST" class="hero-form"
         x-data="{

    /* 🔤 Translations */
    messages: {
        cityNotAvailable: @js(__('messages.city_not_available')),
        fillAllFields: @js(__('messages.fill_all_fields')),
        invalidCity: @js(__('messages.invalid_city'))
    },

    /* 📍 Location */
    locVal: '',
    locAlert: false,
    locAlertSoon: false,
    locOpen: false,
    locSearch: '',

    cities: [
        { val: 'meknes', label: 'Meknès', active: true },
        { val: 'casablanca', label: 'Casablanca', active: false }
    ],

    /* 🧠 Normalize */
    normalize(str) {
        return str.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    },

    /* 🔥 Active cities text */
    getActiveCitiesText() {
        const activeCities = this.cities.filter(c => c.active);

        if (activeCities.length === 0) return '';

        if (activeCities.length === 1) {
            return activeCities[0].label;
        }

        return activeCities.map(c => c.label).join(', ');
    },

    /* 🔎 Filter */
    get filteredCities() {
        if (!this.locSearch.trim()) return this.cities;

        const search = this.normalize(this.locSearch);

        return this.cities.filter(c =>
            this.normalize(c.label).startsWith(search)
        );
    },

    /* ⌨️ Input */
    onLocInput() {
        this.locOpen = true;
        this.locAlert = false;
        this.locAlertSoon = false;

        const search = this.normalize(this.locSearch);

        const match = this.cities.find(c =>
            this.normalize(c.label).startsWith(search)
        );

        this.locVal = match ? match.val : '';
    },

    /* 🖱️ Select */
    selectCity(c) {
        this.locOpen = false;
        this.locSearch = c.label;
        this.locVal = c.val;
    },

    /* 🚀 Submit */
    submitForm(e) {
        e.preventDefault();

        const pDate = document.getElementById('hf_pickup_date').value;
        const pTime = document.getElementById('hf_pickup_time').value;
        const rDate = document.getElementById('hf_return_date').value;
        const rTime = document.getElementById('hf_return_time').value;

        /* ❌ Empty */
        if (!this.locSearch || !pDate || !pTime || !rDate || !rTime) {
            alert(this.messages.fillAllFields);
            return;
        }

        /* 🔍 City */
        const selectedCity = this.cities.find(c => c.val === this.locVal);

        /* ❌ Invalid */
        if (!selectedCity) {
            alert(this.messages.invalidCity);
            return;
        }

        /* ❌ Not active */
        if (!selectedCity.active) {
            alert(this.messages.cityNotAvailable + ' (' + this.getActiveCitiesText() + ')');
            return;
        }

        /* ✅ OK */
        e.target.submit();
    }

}"
              @submit.prevent="submitForm($event)">
            @csrf

            <div class="hf-bar">

            {{-- ── Location ── --}}
            <div class="hf-field hf-field--loc" @click.away="locOpen = false">

                <input type="hidden" name="location" :value="locVal">

                <div class="hf-label">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                    {{ __('messages.pickup_location') }}
                </div>

                <input
                    type="text"
                    x-model="locSearch"
                    @input="onLocInput()"
                    @focus="locOpen = true"
                    @keydown.escape="locOpen = false"
                    class="hf-val"
                    placeholder="{{ __('messages.pickup_location') }}..."
                    autocomplete="off"
                >

                {{-- Alert: empty / no match --}}
                <div class="loc-alert" x-show="locAlert" x-transition style="display:none">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    {{ __('messages.city_coming_soon') }}
                </div>

                {{-- Alert: city selected but not active yet --}}
                <div class="loc-alert loc-alert--soon" x-show="locAlertSoon" x-transition style="display:none">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    {{ __('messages.city_coming_soon') }}
                </div>

                {{-- Dropdown --}}
                <div class="loc-dropdown" x-show="locOpen" style="display:none">
                    <template x-for="c in filteredCities" :key="c.val">
                        <div
                            :class="c.active ? 'loc-item loc-item--active' : 'loc-item loc-item--soon'"
                            @mousedown.prevent="selectCity(c)"
                        >
                            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                            <span x-text="c.label"></span>
                            <span class="loc-soon-badge" x-show="!c.active">Soon</span>
                            <svg x-show="c.val === locVal" viewBox="0 0 20 20" fill="currentColor" class="loc-check">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </template>
                    <div class="loc-empty" x-show="filteredCities.length === 0">
                        {{ __('messages.no_city_found') }}
                    </div>
                </div>
            </div>

            <div class="hf-sep"></div>

            {{-- ── Pickup date ── --}}
            <div class="hf-field">
                <div class="hf-label">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/></svg>
                    {{ __('messages.pickup_date') }}
                </div>
                <input type="text" name="pickup_date" id="hf_pickup_date" class="hf-val" placeholder="{{ __('messages.pickup_date') }}" readonly>
            </div>

            <div class="hf-sep"></div>

            {{-- ── Pickup time ── --}}
            <div class="hf-field hf-field--sm">
                <div class="hf-label">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                    {{ __('messages.pickup_time') }}
                </div>
                <input type="text" name="pickup_time" id="hf_pickup_time" class="hf-val" placeholder="09:00" readonly>
            </div>

            <div class="hf-sep"></div>

            {{-- ── Return date ── --}}
            <div class="hf-field">
                <div class="hf-label">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/></svg>
                    {{ __('messages.return_date') }}
                </div>
                <input type="text" name="return_date" id="hf_return_date" class="hf-val" placeholder="{{ __('messages.return_date') }}" readonly>
            </div>

            <div class="hf-sep"></div>

            {{-- ── Return time ── --}}
            <div class="hf-field hf-field--sm">
                <div class="hf-label">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                    {{ __('messages.return_time') }}
                </div>
                <input type="text" name="return_time" id="hf_return_time" class="hf-val" placeholder="18:00" readonly>
            </div>

            {{-- ── Submit ── --}}
            <button type="submit" class="hf-btn">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
                {{ __('messages.search') }}
            </button>

            </div>{{-- end hf-bar --}}
        </form>
    </div>{{-- end hero-content --}}

    {{-- Stats bar --}}
    <div class="hero-stats-bar">
        <div class="hs-item"><strong>500+</strong><span>{{ __('messages.stat_cars') }}</span></div>
        <div class="hs-div"></div>
        <div class="hs-item"><strong>10K+</strong><span>{{ __('messages.stat_customers') }}</span></div>
        <div class="hs-div"></div>
        <div class="hs-item"><strong>12</strong><span>{{ __('messages.home_cities') }}</span></div>
        <div class="hs-div"></div>
        <div class="hs-item"><strong>24/7</strong><span>{{ __('messages.stat_support') }}</span></div>
    </div>

</section>

{{-- ═══════════════════════════════════════
     ABOUT
═══════════════════════════════════════ --}}
<section class="home-about">
    <div class="ha-inner">
        <div class="ha-img-wrap">
            <img src="/images/hero/CEO-1.jpg" alt="CarRental Morocco">
            <div class="ha-img-badge">
                <strong>6+</strong>
                <span>{{ __('messages.home_years') }}</span>
            </div>
        </div>
        <div class="ha-content">
            <div class="section-tag">{{ __('messages.home_about_tag') }}</div>
            <h2 class="section-h2">
                {{ __('messages.home_about_title') }}<br>
                <span>{{ __('messages.home_about_highlight') }}</span>
            </h2>
            <p class="ha-p1">{{ __('messages.home_about_p1') }}</p>
            <p class="ha-p2">{{ __('messages.home_about_p2') }}</p>
            <a href="{{ route('about') }}" class="ha-btn">
                {{ __('messages.learn_more') }}
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            </a>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════
     FEATURED CARS SLIDER
═══════════════════════════════════════ --}}
<section class="home-cars">
    <div class="hc-header">
        <div class="section-tag">{{ __('messages.home_cars_tag') }}</div>
        <h2 class="section-h2">{{ __('messages.home_cars_title') }} <span>{{ __('messages.home_cars_highlight') }}</span></h2>
    </div>

    <div class="hc-slider-wrap">
        <div class="hc-slider" id="carSlider">
            @php
            $featuredCars = [
                ['name'=>'Lamborghini Urus',     'img'=>asset('images/cars/Lamborghini-Urus.jpg'),     'seats'=>4,'price'=>7500],
                ['name'=>'Mercedes C-Class',     'img'=>asset('images/cars/mercedes-c-class.jpg'),     'seats'=>5,'price'=>6800],
                ['name'=>'Audi RS7',             'img'=>asset('images/cars/audi-rs7.jpg'),             'seats'=>4,'price'=>5400],
                ['name'=>'Range Rover',          'img'=>asset('images/cars/range-rover.jpg'),          'seats'=>5,'price'=>6000],
                ['name'=>'Bentley Bentayga',     'img'=>asset('images/cars/bentley-bentayga.jpg'),     'seats'=>5,'price'=>5200],
                ['name'=>'Porsche Cayenne Turbo','img'=>asset('images/cars/porsche-cayenne.jpg'),      'seats'=>5,'price'=>5900],
            ];
            @endphp

            @foreach($featuredCars as $fc)
            <div class="hc-card">
                <div class="hc-img">
                    <img src="{{ $fc['img'] }}" alt="{{ $fc['name'] }}" loading="lazy">
                    <div class="hc-img-overlay"></div>
                </div>
                <div class="hc-body">
                    <h3>{{ $fc['name'] }}</h3>
                    <div class="hc-specs">
                        <span>🚗 {{ $fc['seats'] }} {{ __('messages.seats') }}</span>
                        <span>⚙️ {{ __('messages.automatic') }}</span>
                    </div>
                    <div class="hc-footer">
                        <a href="{{ route('cars.index') }}" class="hc-view-btn">{{ __('messages.view_details') }}</a>
                        <div class="hc-price">
                            <strong>{{ number_format($fc['price']) }}</strong>
                            <span>{{ __('messages.currency') }}/{{ __('messages.per_day') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <button class="hc-arrow hc-arrow--prev" id="prevBtn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <button class="hc-arrow hc-arrow--next" id="nextBtn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </button>
    </div>

    <div class="hc-cta">
        <a href="{{ route('cars.index') }}" class="ha-btn">
            {{ __('messages.view_all_cars') }}
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
        </a>
    </div>
</section>

{{-- ═══════════════════════════════════════
     SERVICES
═══════════════════════════════════════ --}}
<section class="home-services">
    <div class="hs-header">
        <div class="section-tag">{{ __('messages.home_services_tag') }}</div>
        <h2 class="section-h2">{{ __('messages.home_services_title') }} <span>{{ __('messages.home_services_high') }}</span></h2>
        <p class="hs-desc">{{ __('messages.home_services_desc') }}</p>
    </div>

    <div class="hs-grid">
        @php
        $services = [
            ['icon'=>'🚘','title'=>'home_s1_title','desc'=>'home_s1_desc'],
            ['icon'=>'🛫','title'=>'home_s2_title','desc'=>'home_s2_desc'],
            ['icon'=>'🧑‍✈️','title'=>'home_s3_title','desc'=>'home_s3_desc'],
            ['icon'=>'🌍','title'=>'home_s4_title','desc'=>'home_s4_desc'],
            ['icon'=>'💼','title'=>'home_s5_title','desc'=>'home_s5_desc'],
            ['icon'=>'⭐','title'=>'home_s6_title','desc'=>'home_s6_desc'],
        ];
        @endphp

        @foreach($services as $i => $s)
        <div class="hs-card" style="animation-delay:{{ $i * 0.08 }}s">
            <div class="hs-card-icon">{{ $s['icon'] }}</div>
            <h3>{{ __('messages.'.$s['title']) }}</h3>
            <p>{{ __('messages.'.$s['desc']) }}</p>
        </div>
        @endforeach
    </div>
</section>

{{-- ═══════════════════════════════════════
     CAR CATEGORIES
═══════════════════════════════════════ --}}
<section class="home-cats">
    <div class="hcat-header">
        <div class="section-tag">{{ __('messages.home_cats_tag') }}</div>
        <h2 class="section-h2">{{ __('messages.home_cats_title') }} <span>{{ __('messages.home_cats_high') }}</span></h2>
    </div>

    <div class="hcat-slider-wrap">
        <div class="hcat-slider">
            <div class="hcat-slide">
                @php
                $cats = [
                    ['key'=>'home_cat_economy','img'=>'/images/categories/Lamborghini Urus.jpg'],
                    ['key'=>'home_cat_compact','img'=>'/images/categories/Lamborghini Urus.jpg'],
                    ['key'=>'home_cat_sedan',  'img'=>'/images/categories/Lamborghini Urus.jpg'],
                ];
                @endphp
                @foreach($cats as $c)
                <div class="hcat-card">
                    <img src="{{ $c['img'] }}" alt="{{ __('messages.'.$c['key']) }}" loading="lazy">
                    <div class="hcat-overlay"></div>
                    <h3>{{ __('messages.'.$c['key']) }}</h3>
                    <a href="{{ route('cars.index') }}" class="hcat-arrow">
                        <svg viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    </a>
                </div>
                @endforeach
            </div>
            <div class="hcat-slide" style="display:none">
                @php
                $cats2 = [
                    ['key'=>'home_cat_suv',   'img'=>'/images/categories/Lamborghini Urus.jpg'],
                    ['key'=>'home_cat_luxury','img'=>'/images/categories/Lamborghini Urus.jpg'],
                    ['key'=>'home_cat_family','img'=>'/images/categories/Lamborghini Urus.jpg'],
                ];
                @endphp
                @foreach($cats2 as $c)
                <div class="hcat-card">
                    <img src="{{ $c['img'] }}" alt="{{ __('messages.'.$c['key']) }}" loading="lazy">
                    <div class="hcat-overlay"></div>
                    <h3>{{ __('messages.'.$c['key']) }}</h3>
                    <a href="{{ route('cars.index') }}" class="hcat-arrow">
                        <svg viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    </a>
                </div>
                @endforeach
            </div>
        </div>

        <div class="hcat-dots">
            <span class="hcat-dot hcat-dot--active" data-slide="0"></span>
            <span class="hcat-dot" data-slide="1"></span>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════
     HOW IT WORKS
═══════════════════════════════════════ --}}
<section class="home-process">
    <div class="hp-header">
        <div class="section-tag">{{ __('messages.home_process_tag') }}</div>
        <h2 class="section-h2">{{ __('messages.home_process_title') }} <span>{{ __('messages.home_process_high') }}</span></h2>
        <p class="hp-desc">{{ __('messages.home_process_desc') }}</p>
    </div>

    <div class="hp-grid">
        @php
        $steps = [
            ['num'=>'01','title'=>'home_step1_title','desc'=>'home_step1_desc','icon'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
            ['num'=>'02','title'=>'home_step2_title','desc'=>'home_step2_desc','icon'=>'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z'],
            ['num'=>'03','title'=>'home_step3_title','desc'=>'home_step3_desc','icon'=>'M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3z'],
        ];
        @endphp

        @foreach($steps as $i => $s)
        <div class="hp-card" style="animation-delay:{{ $i * 0.12 }}s">
            <div class="hp-card-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $s['icon'] }}"/>
                </svg>
            </div>
            <h3>{{ __('messages.'.$s['title']) }}</h3>
            <p>{{ __('messages.'.$s['desc']) }}</p>
            <div class="hp-num">{{ $s['num'] }}</div>
        </div>
        @endforeach
    </div>
</section>

{{-- ═══════════════════════════════════════
     TESTIMONIALS
═══════════════════════════════════════ --}}
<section class="home-testi">
    <div class="ht-header">
        <div class="section-tag">{{ __('messages.reviews') }}</div>
        <h2 class="section-h2">{{ __('messages.home_testi_title') }}</h2>
        <p class="ht-sub">{{ __('messages.home_testi_sub') }}</p>
    </div>

    <div class="swiper ht-swiper">
        <div class="swiper-wrapper">
            @php
            $testimonials = [
                ['text'=>'Great service and amazing cars! The booking process was super easy.','name'=>'John Smith','role'=>'Customer','img'=>'https://i.pravatar.cc/100?img=12'],
                ['text'=>'I really enjoyed driving the car. Highly recommended!','name'=>'Linda Cooper','role'=>'Traveller','img'=>'https://i.pravatar.cc/100?img=30'],
                ['text'=>'Very professional team and excellent customer support!','name'=>'Mark Wilson','role'=>'Business Client','img'=>'https://i.pravatar.cc/100?img=47'],
                ['text'=>'Best rental experience I\'ve had so far. Will rent again!','name'=>'Sarah Lee','role'=>'Designer','img'=>'https://i.pravatar.cc/100?img=59'],
                ['text'=>'High quality cars and fast service. Perfect experience!','name'=>'Adam Bruce','role'=>'Engineer','img'=>'https://i.pravatar.cc/100?img=66'],
            ];
            @endphp

            @foreach($testimonials as $t)
            <div class="swiper-slide">
                <div class="ht-card">
                    <div class="ht-stars">★★★★★</div>
                    <p class="ht-text">"{{ $t['text'] }}"</p>
                    <div class="ht-profile">
                        <img src="{{ $t['img'] }}" alt="{{ $t['name'] }}">
                        <div>
                            <strong>{{ $t['name'] }}</strong>
                            <span>{{ $t['role'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <div class="swiper-pagination ht-pagination"></div>
    </div>
</section>

{{-- ═══════════════════════════════════════
     CTA BANNER
═══════════════════════════════════════ --}}
<section class="home-cta" style="background-image:url('{{ asset('images/placeholder/Lamborghini Urus.jpg') }}')">
    <div class="hcta-overlay"></div>
    <div class="hcta-content">
        <h2>{{ __('messages.home_cta_title') }}</h2>
        <p>{{ __('messages.home_cta_desc') }}</p>
        <div class="hcta-btns">
            <a href="https://wa.me/212600123456" class="hcta-btn hcta-btn--wa">
                <svg viewBox="0 0 24 24" fill="currentColor" style="width:20px;height:20px"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                {{ __('messages.home_cta_whatsapp') }}
            </a>
            <a href="{{ route('cars.index') }}" class="hcta-btn hcta-btn--rent">
                {{ __('messages.home_cta_rent') }}
                <svg viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            </a>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════
     BRANDS SCROLL
═══════════════════════════════════════ --}}
<section class="home-brands">
    <div class="hb-label">{{ __('messages.home_brands_label') }}</div>
    <div class="hb-track-wrap">
        <div class="hb-track" id="brandsTrack">
            @php
            $brands = ['Ferrari','Lamborghini','Rolls-Royce','Porsche','Maserati','Mercedes-Benz','BMW','Audi','Range Rover','Bentley'];
            $allBrands = array_merge($brands, $brands);
            @endphp
            @foreach($allBrands as $b)
                <div class="hb-item">{{ $b }}</div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════
     STYLES
═══════════════════════════════════════ --}}
<style>
:root {
    --gold:   #C89D66;
    --gold-d: #B8935E;
    --bg:     #0a0a0a;
    --bg-2:   #0f0f0f;
    --bg-3:   #141414;
    --border: rgba(255,255,255,0.07);
    --text:   #f0f0f0;
    --muted:  #888;
    --hint:   #444;
    --tr:     0.2s ease;
}

/* ── SHARED ── */
.section-tag {
    display: inline-flex; align-items: center; gap: 8px;
    font-size: 0.68rem; font-weight: 700; letter-spacing: 0.2em;
    text-transform: uppercase; color: var(--gold); margin-bottom: 0.75rem;
}
.section-tag::before { content:''; width:20px; height:1px; background:var(--gold); }

.section-h2 {
    font-size: clamp(1.8rem, 4vw, 3rem);
    font-weight: 800; color: var(--text);
    letter-spacing: -0.04em; line-height: 1.15;
    margin-bottom: 1rem;
}
.section-h2 span { color: var(--gold); }

/* ── HERO ── */
.home-hero { position:relative; min-height:100svh; height:auto; overflow:hidden; display:flex; flex-direction:column; }

.hero-carousel { position:absolute; inset:0; z-index:0; }
.hero-slide    { position:absolute; inset:0; background-size:cover; background-position:center; opacity:0; transition:opacity 1.5s ease; }
.hero-slide.active { opacity:1; }
.hero-overlay  { position:absolute; inset:0; background:linear-gradient(to bottom, rgba(10,10,10,0.55) 0%, rgba(10,10,10,0.75) 100%); }

/* Progress bar */
.hero-progress-bar {
    position:absolute; bottom:0; left:0;
    height:2px; width:0%;
    background:var(--gold); z-index:10;
    transition:none;
}

/* Dots */
.hero-dots {
    position:absolute; bottom:20px; left:50%;
    transform:translateX(-50%);
    display:flex; gap:8px; z-index:10;
}
.hero-dot {
    width:24px; height:3px; border-radius:2px;
    background:rgba(255,255,255,0.25);
    cursor:pointer; transition:all 0.35s;
}
.hero-dot--active { background:var(--gold); width:40px; }

/* Hero content */
.hero-content {
    position:relative; z-index:1;
    flex:1 0 auto; display:flex; flex-direction:column;
    align-items:center; justify-content:center;
    padding:120px 2rem 2.5rem;
    gap:2rem;
    text-align:center;
}

.hero-eyebrow {
    display:inline-flex; align-items:center; gap:10px;
    font-size:0.7rem; font-weight:700; letter-spacing:0.25em;
    text-transform:uppercase; color:var(--gold);
    margin-bottom:0.75rem;
}
.hero-eyebrow::before,.hero-eyebrow::after { content:''; width:24px; height:1px; background:var(--gold); }

.hero-title {
    font-size:clamp(2.8rem, 8vw, 6.5rem);
    font-weight:900; color:#fff; letter-spacing:-0.05em;
    line-height:1.0; margin-bottom:0.75rem;
}

.hero-sub { font-size:1.15rem; color:rgba(255,255,255,0.55); margin-bottom:0.5rem; }

/* ── HERO FORM BAR ── */
.hero-form {
    width:100%; max-width:1080px;
}

.hf-bar {
    display:flex;
    align-items:stretch;
    background:rgba(10,10,10,0.88);
    border:1px solid rgba(255,255,255,0.1);
    border-radius:14px;
    overflow:hidden;
    backdrop-filter:blur(24px);
    min-height:88px;
}

.hf-field {
    flex:1; min-width:0;
    display:flex; flex-direction:column;
    justify-content:center;
    padding:14px 20px;
    cursor:pointer;
    transition:background 0.2s;
}
.hf-field:hover { background:rgba(200,157,102,0.05); }
.hf-field--sm { flex:0.75; }

.hf-sep {
    width:1px; flex-shrink:0;
    background:rgba(255,255,255,0.08);
    margin:14px 0;
}

.hf-label {
    display:flex; align-items:center; gap:5px;
    font-size:0.67rem; font-weight:600;
    color:rgba(255,255,255,0.4);
    letter-spacing:0.05em;
    margin-bottom:6px;
    white-space:nowrap; user-select:none;
}
.hf-label svg { width:12px; height:12px; color:var(--gold); flex-shrink:0; }

.hf-val {
    background:transparent !important;
    border:none !important; outline:none !important;
    color:#fff !important; font-size:0.97rem !important;
    font-weight:600 !important; padding:0 !important;
    width:100% !important; cursor:pointer !important;
    -webkit-text-fill-color:#fff !important;
    caret-color:var(--gold);
}
.hf-val::placeholder { color:rgba(255,255,255,0.25) !important; font-weight:400 !important; }
.hf-val:focus { outline:none !important; }

.hf-btn {
    display:flex; align-items:center; justify-content:center; gap:8px;
    padding:0 36px; flex-shrink:0;
    background:var(--gold);
    border:none; color:#fff;
    font-size:0.97rem; font-weight:700;
    cursor:pointer; white-space:nowrap;
    transition:background 0.2s;
    letter-spacing:0.02em;
}
.hf-btn svg { width:16px; height:16px; }
.hf-btn:hover { background:var(--gold-d); }

/* Flatpickr dark override */
.flatpickr-calendar {
    background:#111 !important;
    border:1px solid rgba(200,157,102,0.2) !important;
    border-radius:12px !important;
    box-shadow:0 20px 50px rgba(0,0,0,0.6) !important;
}
.flatpickr-day { color:#ccc !important; }
.flatpickr-day:hover { background:rgba(200,157,102,0.15) !important; }
.flatpickr-day.selected { background:var(--gold) !important; border-color:var(--gold) !important; color:#000 !important; }
.flatpickr-day.inRange  { background:rgba(200,157,102,0.15) !important; border-color:transparent !important; }
.flatpickr-months .flatpickr-month { color:#fff !important; fill:#fff !important; }
.flatpickr-current-month, .flatpickr-monthDropdown-months { color:#fff !important; }
.flatpickr-weekday { color:var(--gold) !important; }
.numInputWrapper span { border-color:rgba(255,255,255,0.1) !important; }
.flatpickr-time input, .flatpickr-time .flatpickr-am-pm { color:#fff !important; background:#111 !important; }
.flatpickr-time input:focus { background:rgba(200,157,102,0.1) !important; }

/* Hero stats bar */
.hero-stats-bar {
    position:relative; z-index:1;
    background:rgba(10,10,10,0.9);
    border-top:1px solid rgba(255,255,255,0.06);
    display:flex; align-items:center; justify-content:center;
    flex-wrap:wrap; gap:0; padding:1.25rem 2rem;
    backdrop-filter:blur(10px);
}
.hs-item { text-align:center; padding:0 2.5rem; }
.hs-item strong { display:block; font-size:1.6rem; font-weight:800; color:var(--gold); letter-spacing:-0.03em; }
.hs-item span   { font-size:0.62rem; color:var(--hint); text-transform:uppercase; letter-spacing:0.1em; }
.hs-div { width:1px; height:35px; background:rgba(255,255,255,0.07); }

@media(max-width:900px) {
    .home-hero { min-height:auto; }
    .hero-content { justify-content:flex-start; padding:110px 1.25rem 1.5rem; gap:1.35rem; }
    .hero-title { font-size:clamp(2.1rem, 8vw, 3.4rem); }
    .hero-sub { font-size:1rem; margin-bottom:0; }
    .hf-bar { flex-wrap:wrap; border-radius:12px; min-height:auto; padding:8px; gap:0; }
    .hf-field { flex:1 1 calc(50% - 1px); border-radius:8px; min-width:0; }
    .hf-field--sm { flex:1 1 calc(50% - 1px); }
    .hf-sep { display:none; }
    .hf-btn { width:100%; border-radius:8px; padding:14px; margin-top:4px; }
    .hs-item { padding:0 1.25rem; }
}

@media(max-width:600px) {
    .hero-content { padding:96px 1rem 1.25rem; }
    .hf-field,
    .hf-field--sm { flex-basis:100%; padding:12px 14px; }
    .hero-stats-bar { padding:1rem; }
    .hs-div { display:none; }
    .hs-item { flex:1 1 45%; padding:0.4rem 0.75rem; }
}

@media(max-height:700px) and (max-width:900px) {
    .hero-content { padding-top:92px; gap:1rem; }
    .hero-eyebrow { margin-bottom:0.35rem; }
    .hero-title { font-size:clamp(1.9rem, 7vw, 2.6rem); margin-bottom:0.35rem; }
    .hf-field { padding:10px 14px; }
    .hero-stats-bar { position:relative; }
}

/* Location dropdown */
.hf-field--loc { position:relative; }

.hf-val--warn { color:#f87171 !important; -webkit-text-fill-color:#f87171 !important; }

.loc-alert {
    position:absolute;
    bottom:calc(100% + 10px);
    left:0;
    background:#1a0a0a;
    border:1px solid rgba(248,113,113,0.3);
    border-radius:10px;
    padding:10px 14px;
    display:flex; align-items:center; gap:8px;
    font-size:0.8rem; color:#f87171;
    white-space:nowrap;
    box-shadow:0 8px 24px rgba(0,0,0,0.5);
    z-index:300;
    pointer-events:none;
}
.loc-alert svg { width:14px; height:14px; flex-shrink:0; }

.hf-val--display {
    font-size:0.97rem; font-weight:600;
    color:#fff; line-height:1;
}

.loc-dropdown {
    position:absolute;
    top:calc(100% + 6px);
    left:-1px;
    width:230px;
    background:#111;
    border:1px solid rgba(200,157,102,0.2);
    border-radius:12px;
    overflow:hidden;
    box-shadow:0 24px 60px rgba(0,0,0,0.7);
    z-index:200;
    padding:4px 0;
}

.loc-item {
    display:flex; align-items:center; gap:10px;
    padding:10px 14px;
    font-size:0.875rem;
    transition:background 0.15s;
    position:relative;
}
.loc-item svg:first-child { width:13px; height:13px; flex-shrink:0; }

.loc-item--active {
    color:#fff; cursor:pointer;
}
.loc-item--active svg:first-child { color:var(--gold); }
.loc-item--active:hover { background:rgba(200,157,102,0.08); }

.loc-item--soon {
    color:rgba(255,255,255,0.22);
    cursor:not-allowed;
    user-select:none;
}
.loc-item--soon svg:first-child { color:rgba(255,255,255,0.12); }

.loc-soon-badge {
    margin-left:auto;
    font-size:0.57rem; font-weight:700;
    letter-spacing:0.1em; text-transform:uppercase;
    background:rgba(255,255,255,0.06);
    color:rgba(255,255,255,0.25);
    padding:2px 8px; border-radius:100px;
    flex-shrink:0;
}

.loc-check {
    width:13px; height:13px;
    color:var(--gold);
    margin-left:auto;
    flex-shrink:0;
}

.loc-empty {
    display:flex; align-items:center; gap:8px;
    padding:12px 14px;
    font-size:0.8rem; color:rgba(255,255,255,0.3);
    font-style:italic;
}
.loc-empty svg { width:13px; height:13px; }


/* ── ABOUT ── */
.home-about {
    background: var(--bg-2);
    border-top:1px solid var(--border);
    border-bottom:1px solid var(--border);
    padding:6rem 2rem;
}
.ha-inner {
    max-width:1100px; margin:0 auto;
    display:grid; grid-template-columns:1fr 1.1fr; gap:5rem; align-items:center;
}
@media(max-width:900px){ .ha-inner{ grid-template-columns:1fr; gap:3rem; } }

.ha-img-wrap { position:relative; }
.ha-img-wrap img {
    width:100%; height:560px; object-fit:cover;
    border-radius:3px; border:1px solid var(--border);
    filter:grayscale(10%) contrast(1.05);
    transition:transform 6s ease;
}
.ha-img-wrap:hover img { transform:scale(1.03); }
.ha-img-badge {
    position:absolute; bottom:-1px; right:-1px;
    background:var(--bg); border:1px solid var(--border);
    padding:1.25rem 1.75rem;
}
.ha-img-badge strong { display:block; font-size:2.2rem; font-weight:800; color:var(--gold); letter-spacing:-0.04em; line-height:1; }
.ha-img-badge span   { font-size:0.65rem; color:var(--muted); text-transform:uppercase; letter-spacing:0.08em; }

.ha-p1 { font-size:0.95rem; color:var(--muted); line-height:1.85; margin-bottom:1rem; }
.ha-p2 { font-size:0.9rem;  color:var(--hint);  line-height:1.8;  margin-bottom:2rem; }

.ha-btn {
    display:inline-flex; align-items:center; gap:8px;
    padding:12px 24px; border:1px solid rgba(200,157,102,0.35);
    border-radius:3px; color:var(--gold); font-size:0.8rem;
    font-weight:600; letter-spacing:0.1em; text-transform:uppercase;
    text-decoration:none; transition:background var(--tr), border-color var(--tr);
}
.ha-btn svg { width:14px; height:14px; transition:transform 0.2s; }
.ha-btn:hover { background:rgba(200,157,102,0.1); border-color:var(--gold); }
.ha-btn:hover svg { transform:translateX(3px); }

/* ── CARS SLIDER ── */
.home-cars { background:var(--bg); padding:6rem 0; }
.hc-header { max-width:1100px; margin:0 auto; padding:0 2rem; margin-bottom:3rem; }

.hc-slider-wrap { position:relative; overflow:hidden; }
.hc-slider { display:flex; gap:20px; padding:0 2rem; transition:transform 0.5s ease; }

.hc-card {
    min-width:360px; background:var(--bg-2);
    border:1px solid var(--border); border-radius:14px;
    overflow:hidden; transition:transform var(--tr), border-color var(--tr);
}
.hc-card:hover { transform:translateY(-4px); border-color:rgba(200,157,102,0.25); }

.hc-img { position:relative; height:220px; overflow:hidden; background:#111; }
.hc-img img { width:100%; height:100%; object-fit:cover; transition:transform 0.5s ease; }
.hc-card:hover .hc-img img { transform:scale(1.06); }
.hc-img-overlay { position:absolute; inset:0; background:linear-gradient(to top, rgba(10,10,10,0.6) 0%, transparent 50%); }

.hc-body { padding:1.1rem; }
.hc-body h3 { font-size:1rem; font-weight:700; color:var(--text); margin-bottom:8px; letter-spacing:-0.02em; }
.hc-specs { display:flex; gap:12px; font-size:0.78rem; color:var(--muted); margin-bottom:1rem; }
.hc-footer { display:flex; align-items:center; justify-content:space-between; }

.hc-view-btn {
    padding:7px 16px; background:rgba(200,157,102,0.1);
    border:1px solid rgba(200,157,102,0.2); border-radius:7px;
    color:var(--gold); font-size:0.75rem; font-weight:700;
    text-decoration:none; transition:all var(--tr);
}
.hc-view-btn:hover { background:rgba(200,157,102,0.18); border-color:var(--gold); }

.hc-price strong { font-size:1.2rem; font-weight:800; color:var(--gold); letter-spacing:-0.03em; }
.hc-price span   { font-size:0.68rem; color:var(--hint); }

.hc-arrow {
    position:absolute; top:50%; transform:translateY(-50%);
    width:44px; height:44px; border-radius:50%;
    background:rgba(10,10,10,0.85); border:1px solid rgba(200,157,102,0.3);
    color:var(--gold); display:flex; align-items:center; justify-content:center;
    cursor:pointer; transition:all var(--tr); z-index:10;
}
.hc-arrow svg { width:18px; height:18px; }
.hc-arrow:hover { background:var(--gold); color:#0a0a0a; }
.hc-arrow--prev { left:16px; }
.hc-arrow--next { right:16px; }

.hc-cta { display:flex; justify-content:center; margin-top:2.5rem; }

/* ── SERVICES ── */
.home-services { background:var(--bg-2); border-top:1px solid var(--border); padding:6rem 2rem; }
.hs-header { max-width:1100px; margin:0 auto; text-align:center; margin-bottom:3rem; }
.hs-desc { font-size:0.95rem; color:var(--muted); max-width:560px; margin:0 auto; }

.hs-grid {
    max-width:1100px; margin:0 auto;
    display:grid; grid-template-columns:repeat(3,1fr); gap:1px;
    background:var(--border); border:1px solid var(--border);
}
@media(max-width:900px){ .hs-grid{ grid-template-columns:1fr 1fr; } }
@media(max-width:600px){ .hs-grid{ grid-template-columns:1fr; } }

.hs-card {
    background:var(--bg-2); padding:2.5rem 2rem;
    transition:background var(--tr);
    animation:fadeUp 0.5s ease both;
}
.hs-card:hover { background:var(--bg-3); }
.hs-card-icon { font-size:2.2rem; margin-bottom:1rem; }
.hs-card h3 { font-size:1rem; font-weight:700; color:var(--text); margin-bottom:0.6rem; letter-spacing:-0.02em; }
.hs-card p  { font-size:0.85rem; color:var(--muted); line-height:1.7; }

/* ── CATEGORIES ── */
.home-cats { background:var(--bg); border-top:1px solid var(--border); padding:6rem 2rem; }
.hcat-header { max-width:1100px; margin:0 auto 2.5rem; }

.hcat-slider-wrap { max-width:1100px; margin:0 auto; overflow:hidden; }
.hcat-slider {}
.hcat-slide { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; }
@media(max-width:700px){ .hcat-slide{ grid-template-columns:1fr; } }

.hcat-card {
    position:relative; height:320px; border-radius:14px; overflow:hidden;
    cursor:pointer;
}
.hcat-card img { width:100%; height:100%; object-fit:cover; transition:transform 0.5s ease; }
.hcat-card:hover img { transform:scale(1.06); }
.hcat-overlay { position:absolute; inset:0; background:linear-gradient(to bottom,transparent 30%,rgba(0,0,0,0.7)); }
.hcat-card h3 { position:absolute; top:20px; left:20px; font-size:1.1rem; font-weight:700; color:#fff; letter-spacing:-0.02em; }
.hcat-arrow {
    position:absolute; bottom:16px; left:16px;
    width:36px; height:36px; border-radius:50%;
    background:var(--gold);
    display:flex; align-items:center; justify-content:center;
    color:#0a0a0a;
    text-decoration:none;
    transition:transform var(--tr), background var(--tr);
}
.hcat-card:hover .hcat-arrow { transform:scale(1.08) rotate(-45deg); background:#d4ab76; }

.hcat-dots { display:flex; justify-content:center; gap:8px; margin-top:1.5rem; }
.hcat-dot {
    width:28px; height:4px; border-radius:2px;
    background:var(--hint); cursor:pointer; transition:background var(--tr), width var(--tr);
}
.hcat-dot--active { background:var(--gold); width:48px; }

/* ── PROCESS ── */
.home-process { background:var(--bg-2); border-top:1px solid var(--border); padding:6rem 2rem; }
.hp-header { max-width:1100px; margin:0 auto; text-align:center; margin-bottom:4rem; }
.hp-desc { font-size:0.95rem; color:var(--muted); max-width:560px; margin:0 auto; }

.hp-grid {
    max-width:1100px; margin:0 auto;
    display:grid; grid-template-columns:repeat(3,1fr); gap:1px;
    background:var(--border); border:1px solid var(--border);
}
@media(max-width:700px){ .hp-grid{ grid-template-columns:1fr; } }

.hp-card {
    background:var(--bg-2); padding:2.75rem 2.25rem 3.5rem;
    position:relative; overflow:visible;
    transition:background var(--tr);
    animation:fadeUp 0.5s ease both;
}
.hp-card:hover { background:var(--bg-3); }

.hp-card-icon {
    width:44px; height:44px; border-radius:11px;
    background:rgba(200,157,102,0.1); border:1px solid rgba(200,157,102,0.2);
    display:flex; align-items:center; justify-content:center;
    color:var(--gold); margin-bottom:1.25rem;
}
.hp-card-icon svg { width:22px; height:22px; }

.hp-card h3 { font-size:1.1rem; font-weight:700; color:var(--text); margin-bottom:0.6rem; letter-spacing:-0.02em; }
.hp-card p  { font-size:0.875rem; color:var(--muted); line-height:1.75; }

.hp-num {
    position:absolute; left:1.5rem; bottom:-1.75rem;
    width:56px; height:56px; border-radius:50%;
    background:linear-gradient(135deg,var(--gold),var(--gold-d));
    color:#0a0a0a; font-weight:900; font-size:0.9rem;
    display:flex; align-items:center; justify-content:center;
    border:3px solid var(--bg-2); box-shadow:0 8px 20px rgba(0,0,0,0.4);
    z-index:3;
}

/* ── TESTIMONIALS ── */
.home-testi { background:var(--bg); border-top:1px solid var(--border); padding:6rem 2rem; }
.ht-header { max-width:900px; margin:0 auto; text-align:center; margin-bottom:3rem; }
.ht-sub { font-size:0.95rem; color:var(--muted); }

.ht-swiper { max-width:1100px; margin:0 auto; padding-bottom:3rem; }

.ht-card {
    background:var(--bg-2); border:1px solid var(--border);
    border-radius:14px; padding:2rem;
    display:flex; flex-direction:column; gap:1rem;
}
.ht-stars { font-size:1.1rem; color:#fbbf24; letter-spacing:2px; }
.ht-text  { font-size:0.9rem; color:var(--muted); line-height:1.8; font-style:italic; flex:1; }
.ht-profile { display:flex; align-items:center; gap:12px; border-top:1px solid var(--border); padding-top:1rem; }
.ht-profile img { width:44px; height:44px; border-radius:50%; object-fit:cover; border:2px solid rgba(200,157,102,0.2); }
.ht-profile strong { display:block; font-size:0.875rem; color:var(--text); font-weight:700; }
.ht-profile span   { font-size:0.72rem; color:var(--hint); }

.ht-pagination .swiper-pagination-bullet { background:var(--hint); opacity:1; }
.ht-pagination .swiper-pagination-bullet-active { background:var(--gold); width:24px; border-radius:3px; }

/* ── CTA ── */
.home-cta {
    position:relative; min-height:480px;
    background-size:cover; background-position:center;
    display:flex; align-items:center; justify-content:center;
    border-top:1px solid var(--border);
}
.hcta-overlay {
    position:absolute; inset:0;
    background:linear-gradient(to right,rgba(10,10,10,0.9),rgba(10,10,10,0.6));
}
.hcta-content {
    position:relative; z-index:1;
    text-align:center; padding:3rem 2rem; max-width:600px;
}
.hcta-content h2 { font-size:clamp(2rem,4vw,3rem); font-weight:800; color:#fff; letter-spacing:-0.04em; margin-bottom:1rem; }
.hcta-content p  { font-size:1rem; color:#aaa; line-height:1.7; margin-bottom:2.5rem; }

.hcta-btns { display:flex; justify-content:center; gap:14px; flex-wrap:wrap; }

.hcta-btn {
    display:inline-flex; align-items:center; gap:9px;
    padding:13px 28px; border-radius:100px;
    font-size:0.875rem; font-weight:700; text-decoration:none;
    transition:all var(--tr);
}
.hcta-btn--wa   { background:var(--gold); color:#0a0a0a; box-shadow:0 4px 14px rgba(200,157,102,0.25); }
.hcta-btn--wa:hover { background:#d4ab76; box-shadow:0 6px 20px rgba(200,157,102,0.4); }
.hcta-btn--rent { border:1px solid rgba(200,157,102,0.4); color:var(--gold); }
.hcta-btn--rent:hover { background:rgba(200,157,102,0.1); border-color:var(--gold); }

/* ── BRANDS ── */
.home-brands {
    background:var(--bg-2);
    padding:2.25rem 0;
    overflow:hidden;
    border-top:1px solid var(--border);
}
.hb-label {
    text-align:center;
    font-size:0.6rem; font-weight:700;
    letter-spacing:0.25em; text-transform:uppercase;
    color:var(--hint); margin-bottom:1.25rem;
}
.hb-track-wrap { overflow:hidden; }
.hb-track {
    display:flex; align-items:center; gap:0;
    width:max-content;
    animation:brandsScroll 45s linear infinite;
}
.hb-track:hover { animation-play-state:paused; }
.hb-item {
    display:flex; align-items:center;
    font-size:1rem; font-weight:700;
    letter-spacing:-0.01em;
    color:rgba(255,255,255,0.1);
    white-space:nowrap;
    padding:0 2.75rem;
    position:relative;
    transition:color 0.3s;
    cursor:default; flex-shrink:0;
    font-family:Georgia,'Times New Roman',serif;
}
.hb-item::after {
    content:'·';
    position:absolute; right:-2px;
    color:rgba(255,255,255,0.08);
    font-size:1.5rem; line-height:1;
}
.hb-item:hover { color:rgba(200,157,102,0.7); }
@keyframes brandsScroll { from{transform:translateX(0)} to{transform:translateX(-50%)} }

/* Shared animation */
@keyframes fadeUp { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:none} }

/* RTL */
[dir="rtl"] .section-tag::before { order:1; }
[dir="rtl"] .ha-btn svg { transform:scaleX(-1); }
[dir="rtl"] .ha-btn:hover svg { transform:scaleX(-1) translateX(-3px); }
[dir="rtl"] .hcta-btn--rent svg { transform:scaleX(-1); }
[dir="rtl"] .hf-input-wrap svg, [dir="rtl"] .hf-select-wrap svg { left:auto; right:10px; }
[dir="rtl"] .hf-input-wrap input, [dir="rtl"] .hf-select-wrap select { padding:9px 30px 9px 10px !important; }
[dir="rtl"] .hp-num { left:auto; right:1.5rem; }
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {

    // ── Hero carousel with progress bar ──
    const heroSlides = document.querySelectorAll('.hero-slide');
    const heroDots   = document.querySelectorAll('.hero-dot');
    const heroBar    = document.getElementById('heroProgress');
    const DURATION   = 5000;
    let heroCur = 0, heroStart = null;

    function heroGo(n) {
        heroSlides[heroCur].classList.remove('active');
        heroDots[heroCur].classList.remove('hero-dot--active');
        heroCur = n;
        heroSlides[heroCur].classList.add('active');
        heroDots[heroCur].classList.add('hero-dot--active');
        heroStart = performance.now();
    }

    function heroTick(ts) {
        if (!heroStart) heroStart = ts;
        const pct = Math.min(((ts - heroStart) / DURATION) * 100, 100);
        heroBar.style.width = pct + '%';
        if (ts - heroStart >= DURATION) heroGo((heroCur + 1) % heroSlides.length);
        requestAnimationFrame(heroTick);
    }

    heroDots.forEach(d => d.addEventListener('click', () => heroGo(parseInt(d.dataset.i))));
    requestAnimationFrame(heroTick);

    // ── Hero Flatpickr ──
    if (typeof flatpickr !== 'undefined') {
        const pickupFp = flatpickr('#hf_pickup_date', {
            dateFormat: 'Y-m-d',
            minDate: 'today',
            disableMobile: true,
            onChange: function(dates) {
                if (dates[0]) returnFp.set('minDate', dates[0]);
            }
        });

        const returnFp = flatpickr('#hf_return_date', {
            dateFormat: 'Y-m-d',
            minDate: 'today',
            disableMobile: true,
        });

        flatpickr('#hf_pickup_time', {
            enableTime: true,
            noCalendar: true,
            dateFormat: 'H:i',
            defaultDate: '09:00',
            time_24hr: true,
            disableMobile: true,
        });

        flatpickr('#hf_return_time', {
            enableTime: true,
            noCalendar: true,
            dateFormat: 'H:i',
            defaultDate: '18:00',
            time_24hr: true,
            disableMobile: true,
        });
    }

    // ── Car slider ──
    const slider  = document.getElementById('carSlider');
    const cardW   = 380;
    let idx = 0;
    const maxIdx  = slider.children.length - 1;

    document.getElementById('nextBtn').addEventListener('click', () => {
        if (idx < maxIdx) { idx++; slider.style.transform = `translateX(-${idx * cardW}px)`; }
    });
    document.getElementById('prevBtn').addEventListener('click', () => {
        if (idx > 0) { idx--; slider.style.transform = `translateX(-${idx * cardW}px)`; }
    });

    // ── Categories dots ──
    const catSlides = document.querySelectorAll('.hcat-slide');
    const catDots   = document.querySelectorAll('.hcat-dot');
    catDots.forEach(dot => {
        dot.addEventListener('click', () => {
            const n = parseInt(dot.dataset.slide);
            catSlides.forEach((s,i) => s.style.display = i === n ? 'grid' : 'none');
            catDots.forEach(d => d.classList.remove('hcat-dot--active'));
            dot.classList.add('hcat-dot--active');
        });
    });

    // ── Swiper testimonials ──
    if (typeof Swiper !== 'undefined') {
        new Swiper('.ht-swiper', {
            loop: true,
            autoplay: { delay: 3000, disableOnInteraction: false },
            speed: 700,
            grabCursor: true,
            slidesPerView: 1,
            spaceBetween: 20,
            pagination: { el: '.ht-pagination', clickable: true },
            breakpoints: { 768: { slidesPerView: 2 }, 1100: { slidesPerView: 3 } }
        });
    }

    // ── Brands pause on hover ──
    const track = document.getElementById('brandsTrack');
    if (track) {
        track.addEventListener('mouseenter', () => track.style.animationPlayState = 'paused');
        track.addEventListener('mouseleave', () => track.style.animationPlayState = 'running');
    }
});
</script>
@endpush

@endsection
