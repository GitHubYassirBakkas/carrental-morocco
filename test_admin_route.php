<?php

echo "Testing admin route registration...\n";

// Check if admin.php exists and has correct content
if (file_exists('routes/admin.php')) {
    echo "✅ routes/admin.php exists\n";
    $content = file_get_contents('routes/admin.php');
    
    if (strpos($content, "Route::middleware(['web', 'auth', 'admin'])") !== false) {
        echo "✅ Admin routes have correct middleware\n";
    } else {
        echo "❌ Admin routes middleware issue\n";
    }
    
    if (strpos($content, "prefix('admin')") !== false) {
        echo "✅ Admin prefix found\n";
    } else {
        echo "❌ Admin prefix missing\n";
    }
    
    if (strpos($content, "name('admin.')") !== false) {
        echo "✅ Admin route name prefix found\n";
    } else {
        echo "❌ Admin route name prefix missing\n";
    }
    
    if (strpos($content, "AdminDashboardController::class") !== false) {
        echo "✅ AdminDashboardController reference found\n";
    } else {
        echo "❌ AdminDashboardController reference missing\n";
    }
} else {
    echo "❌ routes/admin.php missing\n";
}

echo "\nTry accessing: http://127.0.0.1:8000/admin\n";
echo "If still 404, check Laravel logs at: storage/logs/laravel.log\n";
