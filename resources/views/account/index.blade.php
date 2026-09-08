@extends('layouts.user')

@section('title', 'My Account')

@section('content')


<h1 class="text-2xl font-bold mb-6">My Dashboard</h1>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">

    {{-- Profile --}}
    <div class="bg-white p-6 rounded shadow">
        <h3 class="font-semibold mb-2">Profile</h3>
        <p class="text-sm text-gray-600">{{ auth()->user()->email }}</p>

        <a href="{{ route('account') }}"
           class="inline-block mt-4 text-blue-600 font-medium">
            Edit Profile →
        </a>
    </div>

    {{-- Bookings --}}
    <div class="bg-white p-6 rounded shadow">
        <h3 class="font-semibold mb-2">My Bookings</h3>
        <p class="text-sm text-gray-600">View your reservations</p>

        <a href="{{ route('my_booking.index') }}"
           class="inline-block mt-4 text-blue-600 font-medium">
            View Bookings →
        </a>
    </div>

    {{-- Total Spent --}}
    <div class="bg-white p-6 rounded shadow">
        <h3 class="font-semibold mb-2">Total Spent</h3>
        <p class="text-2xl font-bold text-green-600">
            {{ number_format($totalSpent ?? 0, 2) }} MAD
        </p>
    </div>

</div>

@endsection
