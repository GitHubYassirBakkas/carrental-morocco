<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | CarRental</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">

<div class="flex min-h-screen">

    <!-- SIDEBAR -->
    <aside class="w-64 bg-[#0b0d12] text-white flex flex-col">
        <div class="px-6 py-5 text-2xl font-bold text-yellow-400">
            CarRental Admin
        </div>

        <nav class="flex-1 px-4 space-y-2">
            <a href="{{ route('admin.dashboard') }}"
               class="block px-4 py-2 rounded hover:bg-gray-800">
                Dashboard
            </a>

            <a href="{{ route('admin.cars.index') }}"
               class="block px-4 py-2 rounded hover:bg-gray-800">
                Cars
            </a>

             <a href="{{ route('admin.invoices.index') }}"
               class="block px-4 py-2 rounded hover:bg-gray-800">
                Invoices
            </a>

            <a href="{{ route('admin.insurances.index') }}"
               class="block px-4 py-2 rounded hover:bg-gray-800">
                Insurances
            </a>

            <a href="{{ route('admin.bookings.index') }}"
               class="block px-4 py-2 rounded hover:bg-gray-800">
                Bookings
            </a>

            <a href="{{ route('admin.users.index') }}"
               class="block px-4 py-2 rounded hover:bg-gray-800">
                Users
            </a>
            
            <a href="{{ route('admin.email-logs.index') }}"
               class="block px-4 py-2 rounded hover:bg-gray-800">
                Email Logs
            </a>
            
            <a href="{{ route('admin.settings.index') }}"
               class="block px-4 py-2 rounded hover:bg-gray-800">
                Settings
            </a>
            
            <a href="{{ route('admin.reviews.index') }}"
               class="block px-4 py-2 rounded hover:bg-gray-800">
                Reviews
            </a>
            
            <a href="{{ route('admin.locations.index') }}"
               class="block px-4 py-2 rounded hover:bg-gray-800">
                Locations
            </a>
            
            <a href="{{ route('admin.coupons.index') }}"
               class="block px-4 py-2 rounded hover:bg-gray-800">
                Coupons
            </a>

            <a href="{{ route('admin.support.index') }}"
               class="block px-4 py-2 rounded hover:bg-gray-800">
                Support
            </a>
        </nav>

        <div class="px-4 py-4 border-t border-gray-800">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full bg-red-600 hover:bg-red-700 py-2 rounded">
                    Logout
                </button>
            </form>
        </div>
    </aside>

    <!-- MAIN -->
    <main class="flex-1 p-8">
        @yield('content')
    </main>

</div>

@stack('scripts')


</body>
</html>
