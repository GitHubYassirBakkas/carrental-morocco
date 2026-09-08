<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies;

    public function __construct()
    {
        $this->proxies = $this->configuredTrustedProxies();
    }

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;

    private function configuredTrustedProxies(): array|string|null
    {
        $configured = env('TRUSTED_PROXIES');

        if (! is_string($configured) || trim($configured) === '') {
            return null;
        }

        $proxies = array_values(array_filter(array_map('trim', explode(',', $configured))));

        if ($proxies === []) {
            return null;
        }

        if (in_array('*', $proxies, true)) {
            return app()->environment('production') ? null : '*';
        }

        return $proxies;
    }
}
