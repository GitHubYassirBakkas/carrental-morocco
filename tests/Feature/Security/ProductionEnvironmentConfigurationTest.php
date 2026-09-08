<?php

test('production deployment documentation defines required safe environment settings', function () {
    $deployment = file_get_contents(base_path('DEPLOYMENT.md'));

    expect($deployment)->toContain('Do not copy it unchanged to production')
        ->and($deployment)->toContain('APP_ENV=production')
        ->and($deployment)->toContain('APP_DEBUG=false')
        ->and($deployment)->toContain('APP_URL=https://your-domain.example')
        ->and($deployment)->toContain('SESSION_SECURE_COOKIE=true')
        ->and($deployment)->toContain('TRUSTED_PROXIES=')
        ->and($deployment)->toContain('CACHE_STORE=redis')
        ->and($deployment)->toContain('QUEUE_CONNECTION=database')
        ->and($deployment)->toContain('MAIL_MAILER=resend')
        ->and($deployment)->toContain('RESEND_KEY=<secret>')
        ->and($deployment)->toContain('STRIPE_KEY=pk_live_...')
        ->and($deployment)->toContain('STRIPE_SECRET=sk_live_...')
        ->and($deployment)->toContain('STRIPE_WEBHOOK_SECRET=whsec_...')
        ->and($deployment)->toContain('php artisan config:cache')
        ->and($deployment)->toContain('php artisan schedule:run');
});

test('local environment example is explicitly not production guidance and has blank secrets', function () {
    $example = file_get_contents(base_path('.env.example'));

    expect($example)->toContain('Local development template')
        ->and($example)->toContain('Do not copy this file unchanged to production')
        ->and($example)->toContain('APP_ENV=local')
        ->and($example)->toContain('APP_DEBUG=true')
        ->and($example)->toContain('APP_URL=http://localhost')
        ->and($example)->toContain('STRIPE_SECRET=')
        ->and($example)->toContain('STRIPE_WEBHOOK_SECRET=')
        ->and($example)->toContain('RESEND_KEY=')
        ->and($example)->not->toMatch('/^APP_KEY=base64:/m')
        ->and($example)->not->toMatch('/^STRIPE_SECRET=sk_(test|live)_/m')
        ->and($example)->not->toMatch('/^STRIPE_WEBHOOK_SECRET=whsec_/m')
        ->and($example)->not->toMatch('/^RESEND_KEY=re_[A-Za-z0-9]/m');
});

test('production-sensitive config remains environment driven', function () {
    expect(file_get_contents(config_path('app.php')))
        ->toContain("'env' => env('APP_ENV', 'production')")
        ->toContain("'debug' => (bool) env('APP_DEBUG', false)")
        ->toContain("'url' => env('APP_URL', 'http://localhost')");

    expect(file_get_contents(config_path('session.php')))
        ->toContain("'secure' => env('SESSION_SECURE_COOKIE')")
        ->toContain("'http_only' => env('SESSION_HTTP_ONLY', true)")
        ->toContain("'same_site' => env('SESSION_SAME_SITE', 'lax')");

    expect(file_get_contents(config_path('services.php')))
        ->toContain("'key' => env('STRIPE_KEY')")
        ->toContain("'secret' => env('STRIPE_SECRET')")
        ->toContain("'webhook_secret' => env('STRIPE_WEBHOOK_SECRET')")
        ->toContain("'key' => env('RESEND_KEY')");
});

test('trusted proxy configuration is explicit and rejects wildcard trust in production', function () {
    $middleware = file_get_contents(app_path('Http/Middleware/TrustProxies.php'));

    expect($middleware)->toContain("env('TRUSTED_PROXIES')")
        ->and($middleware)->toContain("app()->environment('production') ? null : '*'");
});

test('sensitive local files and generated secrets remain ignored', function () {
    $gitignore = file_get_contents(base_path('.gitignore'));

    expect($gitignore)->toContain('.env')
        ->and($gitignore)->toContain('.env.*')
        ->and($gitignore)->toContain('!.env.example')
        ->and($gitignore)->toContain('*.sql')
        ->and($gitignore)->toContain('/storage/logs/*.log')
        ->and($gitignore)->toContain('/public/storage')
        ->and($gitignore)->toContain('/database/*.sqlite*');
});
