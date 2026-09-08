<?php

use Illuminate\Support\Facades\Config;

test('services resend configuration uses the canonical resend key environment variable', function () {
    $previous = getenv('RESEND_KEY');

    putenv('RESEND_KEY=test-resend-key');
    $_ENV['RESEND_KEY'] = 'test-resend-key';
    $_SERVER['RESEND_KEY'] = 'test-resend-key';

    try {
        $services = require config_path('services.php');

        expect($services['resend']['key'])->toBe('test-resend-key');
    } finally {
        if ($previous === false) {
            putenv('RESEND_KEY');
            unset($_ENV['RESEND_KEY'], $_SERVER['RESEND_KEY']);
        } else {
            putenv('RESEND_KEY='.$previous);
            $_ENV['RESEND_KEY'] = $previous;
            $_SERVER['RESEND_KEY'] = $previous;
        }
    }
});

test('services configuration contains only one resend entry', function () {
    $source = file_get_contents(config_path('services.php'));
    $legacyName = 'RESEND'.'_API_KEY';

    expect(substr_count($source, "'resend' => ["))->toBe(1)
        ->and($source)->not->toContain($legacyName);
});

test('mail configuration can resolve the resend mailer without a real api key', function () {
    Config::set('mail.default', 'resend');

    expect(config('mail.default'))->toBe('resend')
        ->and(config('mail.mailers.resend.transport'))->toBe('resend')
        ->and(config('services.resend'))->toHaveKey('key');
});
