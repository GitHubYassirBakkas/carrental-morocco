<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\RequirePassword as Middleware;

class RequirePassword extends Middleware
{
    /**
     * The URIs that should be excluded from password confirmation.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];
}
