<?php

// Test if admin routes are registered
require_once 'vendor/autoload.php';

try {
    $app = require_once 'bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    echo "Laravel booted successfully\n";
    
    // Get all routes
    $routes = app('router')->getRoutes();
    $adminRoutes = [];
    
    foreach ($routes as $route) {
        $name = $route->getName();
        if ($name && str_contains($name, 'admin.dashboard')) {
            $adminRoutes[] = [
                'uri' => $route->uri(),
                'name' => $name,
                'methods' => $route->methods()
            ];
        }
    }
    
    if (empty($adminRoutes)) {
        echo "❌ No admin.dashboard route found\n";
        
        // Check if admin routes exist at all
        $anyAdminRoutes = [];
        foreach ($routes as $route) {
            $name = $route->getName();
            if ($name && str_contains($name, 'admin.')) {
                $anyAdminRoutes[] = $name;
            }
        }
        
        if (!empty($anyAdminRoutes)) {
            echo "Found these admin routes:\n";
            foreach ($anyAdminRoutes as $routeName) {
                echo "  - $routeName\n";
            }
        } else {
            echo "❌ No admin routes found at all\n";
        }
    } else {
        echo "✅ admin.dashboard route found:\n";
        print_r($adminRoutes);
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
