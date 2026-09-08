<?php

return [
    'headers' => [
        'csp' => [
            'enabled' => env('SECURITY_CSP_ENABLED', true),
            'report_only' => env('SECURITY_CSP_REPORT_ONLY', true),
        ],

        'hsts' => [
            'enabled' => env('SECURITY_HSTS_ENABLED', false),
            'max_age' => 31536000,
        ],
    ],
];
