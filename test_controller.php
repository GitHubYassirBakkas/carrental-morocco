<?php

// Test admin dashboard controller directly
echo "=== Testing Admin Dashboard Controller ===\n";

require_once 'vendor/autoload.php';

try {
    $app = require_once 'bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    echo "✅ Laravel booted successfully\n";
    
    // Test if we can create the controller
    $controller = new \App\Http\Controllers\Admin\AdminDashboardController();
    echo "✅ AdminDashboardController instantiated\n";
    
    // Test if the index method exists
    if (method_exists($controller, 'index')) {
        echo "✅ index method exists\n";
    } else {
        echo "❌ index method missing\n";
    }
    
    echo "\n=== Test Results ===\n";
    echo "1. Try: http://127.0.0.1:8000/admin-test\n";
    echo "2. Try: http://127.0.0.1:8000/admin-no-check (after login)\n";
    echo "3. Try: http://127.0.0.1:8000/admin (after login)\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
