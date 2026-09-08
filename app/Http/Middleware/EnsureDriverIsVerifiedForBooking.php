<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDriverIsVerifiedForBooking
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        $user->loadMissing('customerProfile');

        if ($user->customerProfile?->isDriverVerified()) {
            return $next($request);
        }

        $request->session()->forget('booking_preview');

        return redirect()->route('booking.driver-verification-required');
    }
}
