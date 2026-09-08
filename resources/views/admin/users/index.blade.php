@extends('admin.layouts.app')

@section('content')
<div class="min-h-screen bg-[#0a0e1a] text-gray-100">
    <div class="max-w-7xl mx-auto p-8">

        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-4xl font-bold text-white mb-2">Users Management</h1>
                <p class="text-gray-400">Manage customers and administrators</p>
            </div>
        </div>

        <!-- Filters -->
        <form method="GET" class="bg-[#1a2332] border border-gray-800 rounded-xl p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search -->
                <div>
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}"
                           placeholder="Search name or email..."
                           class="w-full px-4 py-3 bg-black/40 border border-gray-700 rounded-xl text-white placeholder-gray-500">
                </div>

                <!-- Role Filter -->
                <div>
                    <select name="role" class="w-full px-4 py-3 bg-black/40 border border-gray-700 rounded-xl text-white">
                        <option value="">All Roles</option>
                        <option value="user" {{ request('role') == 'user' ? 'selected' : '' }}>Users</option>
                        <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admins</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div>
                    <select name="is_banned" class="w-full px-4 py-3 bg-black/40 border border-gray-700 rounded-xl text-white">
                        <option value="">All Status</option>
                        <option value="0" {{ request('is_banned') === '0' ? 'selected' : '' }}>Active</option>
                        <option value="1" {{ request('is_banned') === '1' ? 'selected' : '' }}>Banned</option>
                    </select>
                </div>

                <!-- Actions -->
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 px-4 py-3 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-xl transition">
                        Filter
                    </button>
                    <a href="{{ route('admin.users.index') }}" class="px-4 py-3 bg-gray-700 hover:bg-gray-600 text-white rounded-xl transition">
                        Reset
                    </a>
                </div>
            </div>
        </form>

        <!-- Alerts -->
        @if(session('success'))
            <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-lg p-4 mb-6">
                <p class="text-emerald-300">{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-500/10 border border-red-500/30 rounded-lg p-4 mb-6">
                <p class="text-red-300">{{ session('error') }}</p>
            </div>
        @endif

        <!-- Users Table -->
        <div class="bg-[#1a2332] border border-gray-800 rounded-xl overflow-hidden">
            <table class="w-full">
                <thead class="bg-[#0f1520] border-b border-gray-800">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">User</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">Role</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">Bookings</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">Reviews</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">Joined</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    @forelse($users as $user)
                        <tr class="hover:bg-[#0f1520] transition-colors">
                            <!-- User Info -->
                            <td class="px-6 py-4">
                                <div>
                                    <p class="text-white font-semibold">{{ $user->name }}</p>
                                    <p class="text-gray-400 text-sm">{{ $user->email }}</p>
                                    @if($user->phone)
                                        <p class="text-gray-500 text-xs">{{ $user->phone }}</p>
                                    @endif
                                </div>
                            </td>

                            <!-- Role -->
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-lg text-xs font-bold
                                    {{ $user->role == 'admin' 
                                        ? 'bg-purple-900/30 text-purple-400 border border-purple-700/50' 
                                        : 'bg-blue-900/30 text-blue-400 border border-blue-700/50' }}">
                                    {{ ucfirst($user->role) }}
                                </span>
                            </td>

                            <!-- Bookings -->
                            <td class="px-6 py-4">
                                <span class="text-white font-bold">{{ $user->bookings_count }}</span>
                            </td>

                            <!-- Reviews -->
                            <td class="px-6 py-4">
                                <span class="text-white font-bold">{{ $user->reviews_count }}</span>
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4">
                                @if($user->is_banned)
                                    <span class="px-3 py-1 bg-red-900/30 text-red-400 border border-red-700/50 rounded-lg text-xs font-bold">
                                        Banned
                                    </span>
                                @else
                                    <span class="px-3 py-1 bg-emerald-900/30 text-emerald-400 border border-emerald-700/50 rounded-lg text-xs font-bold">
                                        Active
                                    </span>
                                @endif
                            </td>

                            <!-- Joined Date -->
                            <td class="px-6 py-4">
                                <span class="text-gray-400 text-sm">{{ $user->created_at->format('M d, Y') }}</span>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- View -->
                                    <a href="{{ route('admin.users.show', $user) }}" 
                                       class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg transition">
                                        View
                                    </a>

                                    <!-- Edit -->
                                    <a href="{{ route('admin.users.edit', $user) }}" 
                                       class="px-3 py-2 bg-orange-600 hover:bg-orange-700 text-white text-xs font-semibold rounded-lg transition">
                                        Edit
                                    </a>

                                    <!-- Ban/Unban -->
                                    @if($user->role !== 'admin')
                                        @if($user->is_banned)
                                            <form action="{{ route('admin.users.unban', $user) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg transition">
                                                    Unban
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('admin.users.ban', $user) }}" method="POST" class="inline" 
                                                  onsubmit="return confirm('Ban this user?')">
                                                @csrf
                                                <button type="submit" class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded-lg transition">
                                                    Ban
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <p class="text-gray-400">No users found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $users->links() }}
        </div>

    </div>
</div>
@endsection
