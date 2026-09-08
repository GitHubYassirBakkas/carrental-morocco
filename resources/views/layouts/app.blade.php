<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Car Rental Morocco')</title>

    {{--
    ════════════════════════════════════════════════
    Zid had l snippet JAWWA <head> f:
      - resources/views/layouts/app.blade.php
      - resources/views/layouts/auth.blade.php

    Zidha ba3d @vite(['resources/css/app.css'])
    ════════════════════════════════════════════════
--}}

<style>
    /* Global font scale */
    html                { font-size: 17px !important; }
    body                { font-size: 1rem !important; line-height: 1.7 !important; -webkit-font-smoothing: antialiased; }

    /* Inputs */
    input, select, textarea, button { font-size: 0.97rem !important; font-family: inherit; }

    /* Labels & small text */
    label, .muted, small { font-size: 0.82rem !important; }

    /* Descriptions & body copy */
    p, .desc, .subtitle  { font-size: 0.94rem !important; line-height: 1.75 !important; }

    /* Card section headers */
    h2 { font-size: clamp(1.1rem, 2vw, 1.5rem) !important; }
    h3 { font-size: clamp(1rem, 1.8vw, 1.3rem) !important; }

    /* Keep large hero titles untouched */
    h1 { font-size: clamp(1.5rem, 4vw, 2.5rem) !important; }

    /* Tailwind text-xs / text-sm override */
    .text-xs  { font-size: 0.78rem !important; }
    .text-sm  { font-size: 0.9rem  !important; }
    .text-base{ font-size: 1rem    !important; }
    .text-lg  { font-size: 1.15rem !important; }
    .text-xl  { font-size: 1.3rem  !important; }
    .text-2xl { font-size: 1.5rem  !important; }
    .text-3xl { font-size: 1.85rem !important; }
    .text-4xl { font-size: 2.2rem  !important; }
    .text-5xl { font-size: 2.8rem  !important; }
</style>

    <!-- Fonts & Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">


    <!-- Swiper -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

    <!-- Alpine -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#C89D66',
                        secondary: '#1A1A1A',
                        accent: '#2A2A2A',
                    }
                }
            }
        }
    </script>

    @stack('styles')
</head>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<body class="font-sans bg-secondary text-white min-h-screen">

    <!-- Navbar -->
    @include('partials.navbar')

    <!-- Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    @include('partials.footer')

    <!-- ✅ Scroll To Top Button -->
    <button id="scrollTopBtn"
    style="
        position: fixed;
        bottom: 24px;
        right: 24px;
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: #C89D66;
        color: #000;
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        box-shadow: 0 10px 25px rgba(0,0,0,.4);
        cursor: pointer;
        transition: all .3s ease;
    "
>
    <i class="fa-solid fa-arrow-up"></i>
</button>

<script>
(function () {
    const btn = document.getElementById("scrollTopBtn");

    window.addEventListener("scroll", function () {
        if (window.scrollY > 300) {
            btn.style.display = "flex";
        } else {
            btn.style.display = "none";
        }
    });

    btn.addEventListener("click", function () {
        window.scrollTo({
            top: 0,
            behavior: "smooth"
        });
    });
})();
</script>
    <!-- Swiper -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    @stack('scripts')
</body>
</html>
