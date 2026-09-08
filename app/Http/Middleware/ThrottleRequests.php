<?php

namespace App\Http\Middleware;

use Illuminate\Routing\Middleware\ThrottleRequests as Middleware;

class ThrottleRequests extends Middleware
{
    /**
     * The URIs that should be excluded from throttling.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];
}
