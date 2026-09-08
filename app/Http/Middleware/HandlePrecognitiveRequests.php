<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests as Middleware;

class HandlePrecognitiveRequests extends Middleware
{
    /**
     * The middleware aliases.
     *
     * @var array
     */
    protected $middleware = [
        \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
    ];
}
