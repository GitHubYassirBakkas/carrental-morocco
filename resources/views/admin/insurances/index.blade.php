@extends('admin.layouts.app')

@section('content')
<div class="min-h-screen bg-[#0a0e1a] text-gray-100">
    <div class="max-w-7xl mx-auto p-8">

        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-4xl font-bold text-white mb-2">Insurance Plans</h1>
                <p class="text-gray-400">Manage insurance options for your rental cars</p>
            </div>
            <a href="{{ route('admin.insurances.create') }}" 
               class="px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-xl transition-all flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add New Insurance
            </a>
        </div>

        <!-- Alerts -->
        @if(session('success'))
            <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-lg p-4 mb-6 flex items-start gap-3">
                <svg class="w-5 h-5 text-emerald-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-emerald-300">{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-500/10 border border-red-500/30 rounded-lg p-4 mb-6 flex items-start gap-3">
                <svg class="w-5 h-5 text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-red-300">{{ session('error') }}</p>
            </div>
        @endif

        <!-- Insurance Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @forelse($insurances as $insurance)
                <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6 relative">
                    
                    <!-- Status Badge -->
                    <div class="absolute top-6 right-6">
                        <span class="px-3 py-1 text-xs rounded-lg font-semibold
                            {{ $insurance->is_active 
                                ? 'bg-emerald-900/30 text-emerald-400 border border-emerald-700/50' 
                                : 'bg-gray-700 text-gray-400 border border-gray-600' }}">
                            {{ $insurance->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <!-- Type Badge -->
                    <div class="mb-4">
                        <span class="px-3 py-1 rounded-lg text-xs font-bold
                            @if($insurance->type == 'basic') bg-blue-900/30 text-blue-400 border border-blue-700/50
                            @elseif($insurance->type == 'standard') bg-yellow-900/30 text-yellow-400 border border-yellow-700/50
                            @elseif($insurance->type == 'premium') bg-green-900/30 text-green-400 border border-green-700/50
                            @endif">
                            {{ strtoupper($insurance->type) }}
                        </span>
                    </div>

                    <!-- Name -->
                    <h3 class="text-2xl font-bold text-white mb-2">
                        {{ $insurance->name }}
                    </h3>

                    <!-- Description -->
                    <p class="text-gray-400 text-sm mb-4 line-clamp-2">
                        {{ $insurance->description }}
                    </p>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div class="bg-[#0f1520] border border-gray-700 rounded-lg p-3">
                            <p class="text-xs text-gray-500 mb-1">Daily Rate</p>
                            <p class="text-orange-400 font-bold">{{ number_format($insurance->daily_rate, 0) }} MAD</p>
                        </div>

                        <div class="bg-[#0f1520] border border-gray-700 rounded-lg p-3">
                            <p class="text-xs text-gray-500 mb-1">Max Coverage</p>
                            <p class="text-emerald-400 font-bold">{{ number_format($insurance->max_coverage, 0) }} MAD</p>
                        </div>

                        <div class="bg-[#0f1520] border border-gray-700 rounded-lg p-3">
                            <p class="text-xs text-gray-500 mb-1">Deductible</p>
                            <p class="text-yellow-400 font-bold">{{ number_format($insurance->deductible, 0) }} MAD</p>
                        </div>

                        <div class="bg-[#0f1520] border border-gray-700 rounded-lg p-3">
                            <p class="text-xs text-gray-500 mb-1">Cars Using</p>
                            <p class="text-white font-bold">{{ $insurance->cars_count }}</p>
                        </div>
                    </div>

                    <!-- Features -->
                    @if($insurance->features && count($insurance->features) > 0)
                        <div class="mb-4">
                            <p class="text-xs text-gray-500 mb-2">Features:</p>
                            <div class="flex flex-wrap gap-1">
                                @foreach(array_slice($insurance->features, 0, 3) as $feature)
                                    <span class="text-xs bg-gray-800/50 text-gray-300 px-2 py-1 rounded border border-gray-700">
                                        {{ Str::limit($feature, 20) }}
                                    </span>
                                @endforeach
                                @if(count($insurance->features) > 3)
                                    <span class="text-xs text-gray-500">
                                        +{{ count($insurance->features) - 3 }} more
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Actions -->
                    <div class="flex gap-2 pt-4 border-t border-gray-700">
                        <a href="{{ route('admin.insurances.edit', $insurance) }}" 
                           class="flex-1 px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-lg transition-all text-center text-sm">
                            Edit
                        </a>
                        
                        <form action="{{ route('admin.insurances.destroy', $insurance) }}" 
                              method="POST" 
                              onsubmit="return confirm('Delete this insurance?')"
                              class="flex-1">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition-all text-sm">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="col-span-3 text-center py-12">
                    <svg class="w-16 h-16 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-gray-400 text-lg mb-4">No insurance plans yet</p>
                    <a href="{{ route('admin.insurances.create') }}" 
                       class="inline-flex items-center gap-2 px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-xl transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Create First Insurance
                    </a>
                </div>
            @endforelse
        </div>

    </div>
</div>
@endsection