<?php

namespace App\Providers;
use Illuminate\Support\Facades\Route;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Where to redirect users after login & email verification.
     */
    public const HOME = '/';
  public function boot(): void
{
    $this->routes(function () {

        Route::middleware('web')
            ->group(base_path('routes/web.php'));

        Route::middleware('web')
            ->group(base_path('routes/auth.php'));

       
    });
}

}
