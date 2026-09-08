@extends('admin.layouts.app')

@section('content')
<div class="min-h-screen bg-[#0a0e1a] p-8">
    
    <!-- Header -->
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-4xl font-bold text-white mb-2">Locations Management</h1>
            <p class="text-gray-400">Manage pickup and dropoff locations</p>
        </div>
        <a href="{{ route('admin.locations.create') }}" 
           class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add New Location
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
        
        <!-- Total -->
        <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-blue-500/10 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-white mb-1">{{ $stats['total'] }}</div>
            <div class="text-sm text-gray-400">Total Locations</div>
        </div>

        <!-- Active -->
        <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-green-500/10 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-white mb-1">{{ $stats['active'] }}</div>
            <div class="text-sm text-gray-400">Active Locations</div>
        </div>

        <!-- Inactive -->
        <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-red-500/10 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-white mb-1">{{ $stats['inactive'] }}</div>
            <div class="text-sm text-gray-400">Inactive Locations</div>
        </div>

    </div>

    <!-- Locations Table -->
    <div class="bg-[#1a2332] border border-gray-800 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-[#0f1520] text-xs">
                    <tr>
                        <th class="px-6 py-3 text-left text-gray-400 font-semibold uppercase tracking-wider">Location</th>
                        <th class="px-6 py-3 text-left text-gray-400 font-semibold uppercase tracking-wider">Contact</th>
                        <th class="px-6 py-3 text-center text-gray-400 font-semibold uppercase tracking-wider">Hours</th>
                        <th class="px-6 py-3 text-center text-gray-400 font-semibold uppercase tracking-wider">Coordinates</th>
                        <th class="px-6 py-3 text-center text-gray-400 font-semibold uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-center text-gray-400 font-semibold uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    @forelse($locations as $location)
                        <tr class="hover:bg-[#0f1520] transition-colors">
                            <!-- Location Info -->
                            <td class="px-6 py-4">
                                <div class="font-semibold text-white">{{ $location->name }}</div>
                                <div class="text-sm text-gray-400">{{ $location->address }}</div>
                                <div class="text-xs text-gray-500">{{ $location->city }}, {{ $location->country }}</div>
                            </td>

                            <!-- Contact -->
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-300">{{ $location->phone }}</div>
                                @if($location->email)
                                    <div class="text-xs text-gray-500">{{ $location->email }}</div>
                                @endif
                            </td>

                            <!-- Working Hours -->
                            <td class="px-6 py-4 text-center">
                                @if($location->opening_time && $location->closing_time)
                                    <div class="text-sm text-gray-300">{{ substr($location->opening_time, 0, 5) }} - {{ substr($location->closing_time, 0, 5) }}</div>
                                @else
                                    <span class="text-xs text-gray-500">Not set</span>
                                @endif
                            </td>

                            <!-- Coordinates -->
                            <td class="px-6 py-4 text-center">
                                @if($location->has_coordinates)
                                    <a href="{{ $location->map_link }}" 
                                       target="_blank"
                                       class="text-blue-400 hover:text-blue-300 text-xs">
                                        View Map →
                                    </a>
                                @else
                                    <span class="text-xs text-gray-500">No GPS</span>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4 text-center">
                                @if($location->is_active)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-900/30 text-green-400 border border-green-700/50">
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-red-900/30 text-red-400 border border-red-700/50">
                                        Inactive
                                    </span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    
                                    <!-- Edit -->
                                    <a href="{{ route('admin.locations.edit', $location) }}" 
                                       class="p-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition"
                                       title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>

                                    <!-- Toggle Status -->
                                    <form action="{{ route('admin.locations.toggle', $location) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" 
                                                class="p-2 {{ $location->is_active ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-green-600 hover:bg-green-700' }} text-white rounded-lg transition"
                                                title="{{ $location->is_active ? 'Deactivate' : 'Activate' }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                            </svg>
                                        </button>
                                    </form>

                                    <!-- Delete -->
                                    <form action="{{ route('admin.locations.destroy', $location) }}" 
                                          method="POST" 
                                          class="inline"
                                          onsubmit="return confirm('Are you sure you want to delete this location?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="p-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition"
                                                title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                No locations found. Add your first location!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($locations->hasPages())
            <div class="px-6 py-4 border-t border-gray-800">
                {{ $locations->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
