<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        // Check if language is in session
        if (Session::has('locale')) {
            $locale = Session::get('locale');
        }
        // Check if language is in URL
        elseif ($request->has('lang')) {
            $locale = $request->get('lang');
            Session::put('locale', $locale);
        }
        // Use default
        else {
            $locale = config('app.locale');
        }

        // Validate locale
        $availableLocales = ['en', 'fr', 'ar'];
        if (in_array($locale, $availableLocales)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
