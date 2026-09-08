@extends('layouts.app')

@section('title', __('messages.wishlist_title'))

@section('content')
<div class="container mx-auto px-6 py-12">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-white mb-2">{{ __('messages.wishlist_title') }}</h1>
        <p class="text-gray-400">{{ __('messages.wishlist_subtitle') }}</p>
    </div>

    @if($wishlists->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($wishlists as $wishlist)
                @php
                    $car = $wishlist->car;
                @endphp
                <div class="bg-[#0f0f0f] border border-white/5 rounded-2xl overflow-hidden hover:border-[#C89D66]/30 transition-all duration-300">
                    {{-- Car Image --}}
                    <div class="relative h-48">
                        <img src="{{ $car->image_url }}"
                             alt="{{ $car->full_name }}"
                             class="w-full h-full object-cover">

                        @if($car->is_available)
                            <div class="absolute top-3 right-3 bg-green-500/20 text-green-400 text-xs px-2 py-1 rounded-full border border-green-500/30">
                                {{ __('messages.wishlist_available') }}
                            </div>
                        @else
                            <div class="absolute top-3 right-3 bg-red-500/20 text-red-400 text-xs px-2 py-1 rounded-full border border-red-500/30">
                                {{ __('messages.wishlist_unavailable') }}
                            </div>
                        @endif
                    </div>

                    {{-- Car Info --}}
                    <div class="p-5">
                        <h3 class="text-lg font-bold text-white mb-1">{{ $car->full_name }}</h3>

                        @if($car->location)
                            <p class="text-gray-500 text-sm mb-3">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                {{ $car->location->name }}
                            </p>
                        @endif

                        {{-- Specs --}}
                        <div class="flex flex-wrap gap-2 mb-4">
                            <span class="text-xs bg-white/5 text-gray-400 px-2 py-1 rounded">
                                {{ ui_transmission($car->transmission) }}
                            </span>
                            <span class="text-xs bg-white/5 text-gray-400 px-2 py-1 rounded">
                                {{ ui_fuel_type($car->fuel_type) }}
                            </span>
                            <span class="text-xs bg-white/5 text-gray-400 px-2 py-1 rounded">
                                {{ $car->seats }} {{ __('messages.car_seats') }}
                            </span>
                        </div>

                        {{-- Price --}}
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <span class="text-2xl font-bold text-[#C89D66]">{{ number_format($car->price_per_day, 0) }}</span>
                                <span class="text-gray-500 text-sm">{{ __('messages.car_per_day') }}</span>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex gap-2">
                            <a href="{{ route('cars.details', $car) }}"
                               class="flex-1 bg-gradient-to-r from-[#C89D66] to-[#B8935E] text-white text-center py-2.5 rounded-xl text-sm font-semibold hover:opacity-90 transition-opacity">
                                {{ __('messages.car_view_details') }}
                            </a>
                            <form method="POST" action="{{ route('wishlist.destroy', $car) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="bg-red-500/10 text-red-400 px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-red-500/20 transition-colors"
                                        title="{{ __('messages.wishlist_remove') }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-16">
            <div class="w-20 h-20 bg-white/5 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
            </div>
            <h3 class="text-xl font-semibold text-white mb-2">{{ __('messages.wishlist_empty') }}</h3>
            <p class="text-gray-500 mb-6">{{ __('messages.wishlist_empty_desc') }}</p>
            <a href="{{ route('cars.index') }}"
               class="inline-block bg-gradient-to-r from-[#C89D66] to-[#B8935E] text-white px-6 py-3 rounded-xl text-sm font-semibold hover:opacity-90 transition-opacity">
                {{ __('messages.dashboard_browse_cars') }}
            </a>
        </div>
    @endif
</div>
@endsection
