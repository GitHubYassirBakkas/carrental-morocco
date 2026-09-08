<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\SetCacheHeaders as Middleware;

class SetCacheHeaders extends Middleware
{
    /**
     * The cache headers that should be applied.
     *
     * @var array
     */
    protected $headers = [
        'max-age' => 3600,
        'etag' => true,
        'last-modified' => true,
    ];
}
