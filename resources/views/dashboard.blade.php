@extends('layouts.app')

@section('content')
<div class="container mx-auto mt-10">
    <h1 class="text-3xl font-bold mb-4">
        Welcome, {{ Auth::user()->name }} 👋
    </h1>

    <div class="bg-white shadow rounded-xl p-6">
        <p class="text-gray-600">
            This is your dashboard.
        </p>

        <a href="{{ route('profile.edit') }}"
           class="inline-block mt-4 px-6 py-2 bg-lime-500 text-black rounded-lg">
            Edit Profile
        </a>
    </div>
</div>
@endsection
