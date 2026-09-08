<?php

$phpunitConfig = dirname(__DIR__).'/phpunit.xml';

if (is_file($phpunitConfig)) {
    $phpunit = simplexml_load_file($phpunitConfig);

    foreach ($phpunit?->php?->server ?? [] as $server) {
        $name = (string) $server['name'];

        if (in_array($name, ['DB_CONNECTION', 'DB_DATABASE'], true)) {
            $value = (string) $server['value'];
            putenv($name.'='.$value);
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

$testDbConnection = $_SERVER['DB_CONNECTION'] ?? $_ENV['DB_CONNECTION'] ?? env('DB_CONNECTION');

if ($testDbConnection !== 'sqlite') {
    throw new RuntimeException(
        'ABORTED: Tests are trying to run on non-sqlite database.'
    );
}

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

function stripeWebhookSignature(string $payload, string $secret = 'whsec_test', ?int $timestamp = null): string
{
    $timestamp ??= time();
    $signature = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

    return "t={$timestamp},v1={$signature}";
}

function postStripeWebhook(mixed $test, array $payload, string $secret = 'whsec_test'): \Illuminate\Testing\TestResponse
{
    config(['services.stripe.webhook_secret' => $secret]);

    $rawPayload = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

    return $test->call('POST', '/stripe/webhook', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_STRIPE_SIGNATURE' => stripeWebhookSignature($rawPayload, $secret),
    ], $rawPayload);
}
