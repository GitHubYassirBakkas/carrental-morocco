<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Auth') — CarRental Morocco</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css'])
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @yield('styles')

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            height: 100%;
            min-height: 100vh;
            width: 100%;
            background: #0a0a0a;
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
        }

        body {
            display: flex;
            flex-direction: column;
        }
    </style>
</head>
<body>
    @yield('content')
</body>
</html>