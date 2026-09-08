<?php

require_once 'vendor/autoload.php';

try {
    $app = require_once 'bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    echo "Testing route name resolution...\n";
    
    // Try to get the URL for the admin.dashboard route
    try {
        $url = route('admin.dashboard');
        echo "✅ admin.dashboard route resolved to: $url\n";
    } catch (Exception $e) {
        echo "❌ admin.dashboard route not found: " . $e->getMessage() . "\n";
    }
    
    // Try to get the URL for a hardcoded admin path
    try {
        $url = url('/admin');
        echo "✅ /admin URL resolved to: $url\n";
    } catch (Exception $e) {
        echo "❌ /admin URL failed: " . $e->getMessage() . "\n";
    }
    
    // List all registered routes with admin in the name
    $routes = app('router')->getRoutes();
    $adminRoutes = [];
    
    foreach ($routes as $route) {
        $name = $route->getName();
        if ($name && str_contains($name, 'admin')) {
            $adminRoutes[] = [
                'uri' => $route->uri(),
                'name' => $name,
                'methods' => implode(',', $route->methods())
            ];
        }
    }
    
    if (!empty($adminRoutes)) {
        echo "\n📋 Found " . count($adminRoutes) . " admin routes:\n";
        foreach ($adminRoutes as $route) {
            echo "  {$route['methods']} {$route['uri']} -> {$route['name']}\n";
        }
    } else {
        echo "\n❌ No admin routes found\n";
    }
    
} catch (Exception $e) {
    echo "Bootstrap error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
