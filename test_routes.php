<?php

require_once 'vendor/autoload.php';
require_once 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$routes = app('router')->getRoutes();

echo "=== ADMIN ROUTES ===\n";
foreach ($routes as $route) {
    $uri = $route->uri();
    $name = $route->getName();
    
    if (str_contains($uri, 'admin') || ($name && str_contains($name, 'admin'))) {
        echo "URI: {$uri} | Name: {$name} | Method: " . implode('|', $route->methods()) . "\n";
    }
}

echo "\n=== ALL ROUTES COUNT ===\n";
echo "Total routes: " . $routes->count() . "\n";
