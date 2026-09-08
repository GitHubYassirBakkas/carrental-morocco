<?php

// Simple test to check if routes are registered
echo "Testing Laravel routes...\n";

// Check if required files exist
$requiredFiles = [
    'routes/admin.php',
    'app/Http/Controllers/Admin/AdminDashboardController.php',
    'app/Http/Middleware/AdminMiddleware.php',
    'app/Providers/RouteServiceProvider.php'
];

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "✓ $file exists\n";
    } else {
        echo "✗ $file missing\n";
    }
}

// Check admin.php content
if (file_exists('routes/admin.php')) {
    $content = file_get_contents('routes/admin.php');
    if (strpos($content, 'AdminDashboardController') !== false) {
        echo "✓ AdminDashboardController reference found in routes\n";
    } else {
        echo "✗ AdminDashboardController reference missing in routes\n";
    }
    
    if (strpos($content, "prefix('admin')") !== false) {
        echo "✓ Admin prefix found in routes\n";
    } else {
        echo "✗ Admin prefix missing in routes\n";
    }
}

echo "\nTest completed.\n";
