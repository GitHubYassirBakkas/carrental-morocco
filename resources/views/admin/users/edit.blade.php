@extends('admin.layouts.app')

@section('content')
<div class="min-h-screen bg-[#0a0e1a] text-gray-100">
    <div class="max-w-4xl mx-auto p-8">

        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-sm mb-6">
            <a href="{{ route('admin.users.index') }}" class="text-gray-400 hover:text-white">Users</a>
            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <a href="{{ route('admin.users.show', $user) }}" class="text-gray-400 hover:text-white">{{ $user->name }}</a>
            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-white font-medium">Edit</span>
        </nav>

        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-white mb-2">Edit User</h1>
            <p class="text-gray-400">Update user information and settings</p>
        </div>

        <!-- Validation Errors -->
        @if($errors->any())
            <div class="bg-red-500/10 border border-red-500/30 rounded-lg p-4 mb-6">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="text-red-300 font-semibold mb-2">Please fix the following errors:</p>
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($errors->all() as $error)
                                <li class="text-red-300 text-sm">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <!-- Form -->
        <form action="{{ route('admin.users.update', $user) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Personal Information -->
            <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-8">
                <h2 class="text-xl font-bold text-white mb-6">Personal Information</h2>

                <div class="space-y-6">
                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            Full Name *
                        </label>
                        <input type="text" 
                               name="name" 
                               value="{{ old('name', $user->name) }}"
                               required
                               class="w-full px-4 py-3 bg-black/40 border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                        @error('name')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            Email Address *
                        </label>
                        <input type="email" 
                               name="email" 
                               value="{{ old('email', $user->email) }}"
                               required
                               class="w-full px-4 py-3 bg-black/40 border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                        @error('email')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Phone -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            Phone Number
                        </label>
                        <input type="text" 
                               name="phone" 
                               value="{{ old('phone', $user->phone) }}"
                               placeholder="+212 6XX-XXXXXX"
                               class="w-full px-4 py-3 bg-black/40 border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                        @error('phone')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Role -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            User Role *
                        </label>
                        <select name="role" 
                                required
                                class="w-full px-4 py-3 bg-black/40 border border-gray-700 rounded-xl text-white focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                            <option value="user" {{ old('role', $user->role) == 'user' ? 'selected' : '' }}>User</option>
                            <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Admin</option>
                        </select>
                        @error('role')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-gray-500 text-xs mt-2">
                            ⚠️ Admins have full access to the admin panel
                        </p>
                    </div>
                </div>
            </div>

            <!-- Password Change (Optional) -->
            <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-8">
                <h2 class="text-xl font-bold text-white mb-2">Change Password</h2>
                <p class="text-gray-400 text-sm mb-6">Leave blank to keep current password</p>

                <div class="space-y-6">
                    <!-- New Password -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            New Password
                        </label>
                        <input type="password" 
                               name="password" 
                               placeholder="Enter new password (min 8 characters)"
                               class="w-full px-4 py-3 bg-black/40 border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                        @error('password')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            Confirm New Password
                        </label>
                        <input type="password" 
                               name="password_confirmation" 
                               placeholder="Confirm new password"
                               class="w-full px-4 py-3 bg-black/40 border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    </div>
                </div>
            </div>

            <!-- User Status Info -->
            <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-8">
                <h2 class="text-xl font-bold text-white mb-6">Account Status</h2>

                <div class="space-y-4">
                    <!-- Current Status Display -->
                    <div class="flex items-center justify-between p-4 bg-black/30 rounded-lg">
                        <div>
                            <p class="text-sm text-gray-400 mb-1">Current Status</p>
                            @if($user->is_banned)
                                <span class="px-3 py-1 bg-red-900/30 text-red-400 border border-red-700/50 rounded-lg text-sm font-bold">
                                    🚫 Banned
                                </span>
                            @else
                                <span class="px-3 py-1 bg-emerald-900/30 text-emerald-400 border border-emerald-700/50 rounded-lg text-sm font-bold">
                                    ✅ Active
                                </span>
                            @endif
                        </div>
                        
                        @if($user->role !== 'admin')
                            <div>
                                @if($user->is_banned)
                                    <a href="{{ route('admin.users.show', $user) }}" 
                                       class="text-emerald-400 hover:text-emerald-300 text-sm font-semibold">
                                        Use "Unban" button in user details →
                                    </a>
                                @else
                                    <a href="{{ route('admin.users.show', $user) }}" 
                                       class="text-red-400 hover:text-red-300 text-sm font-semibold">
                                        Use "Ban" button in user details →
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>

                    <!-- Account Info -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 bg-black/30 rounded-lg">
                            <p class="text-xs text-gray-500 mb-1">Account Created</p>
                            <p class="text-white text-sm">{{ $user->created_at->format('M d, Y') }}</p>
                            <p class="text-gray-400 text-xs">{{ $user->created_at->diffForHumans() }}</p>
                        </div>

                        <div class="p-4 bg-black/30 rounded-lg">
                            <p class="text-xs text-gray-500 mb-1">Last Updated</p>
                            <p class="text-white text-sm">{{ $user->updated_at->format('M d, Y') }}</p>
                            <p class="text-gray-400 text-xs">{{ $user->updated_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-4 pt-6">
                <button type="submit" 
                        class="flex-1 px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-xl transition-all shadow-lg">
                    💾 Update User
                </button>
                
                <a href="{{ route('admin.users.show', $user) }}" 
                   class="flex-1 px-6 py-3 bg-gray-700 hover:bg-gray-600 text-white font-semibold rounded-xl transition-all text-center">
                    Cancel
                </a>
            </div>
        </form>

    </div>
</div>
@endsection
