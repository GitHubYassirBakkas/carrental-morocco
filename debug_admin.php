<?php

echo "=== ROUTE DEBUG ===\n";

// Check if admin routes file exists
if (file_exists('routes/admin.php')) {
    echo "✅ routes/admin.php exists\n";
    
    $content = file_get_contents('routes/admin.php');
    
    // Check for admin route definition
    if (strpos($content, "prefix('admin')") !== false) {
        echo "✅ Admin prefix found\n";
    } else {
        echo "❌ Admin prefix missing\n";
    }
    
    if (strpos($content, "AdminDashboardController") !== false) {
        echo "✅ AdminDashboardController found\n";
    } else {
        echo "❌ AdminDashboardController missing\n";
    }
    
    // Check if the route is properly defined
    if (strpos($content, "Route::get('/', [AdminDashboardController::class, 'index'])") !== false) {
        echo "✅ Admin dashboard route defined\n";
    } else {
        echo "❌ Admin dashboard route not defined\n";
    }
} else {
    echo "❌ routes/admin.php missing\n";
}

// Check if controller exists
if (file_exists('app/Http/Controllers/Admin/AdminDashboardController.php')) {
    echo "✅ AdminDashboardController exists\n";
} else {
    echo "❌ AdminDashboardController missing\n";
}

echo "\nTry accessing: http://127.0.0.1:8000/admin-test\n";
echo "This should show 'Admin routes are working!'\n";

echo "\nIf admin-test works but admin doesn't, the issue is with middleware.\n";
