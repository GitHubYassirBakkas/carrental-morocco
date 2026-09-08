<?php

echo "=== ROUTING TROUBLESHOOTING ===\n\n";

echo "1. Testing basic routing...\n";
echo "   Try: http://127.0.0.1:8000/test-route\n";
echo "   Should show: 'Routing is working!'\n\n";

echo "2. Testing admin route...\n";
echo "   Try: http://127.0.0.1:8000/admin\n";
echo "   Should redirect to login if not authenticated\n";
echo "   Should show admin dashboard if authenticated as admin\n\n";

echo "3. Current route files:\n";
$routeFiles = ['web.php', 'admin.php', 'auth.php'];
foreach ($routeFiles as $file) {
    if (file_exists("routes/$file")) {
        echo "   ✅ routes/$file exists\n";
    } else {
        echo "   ❌ routes/$file missing\n";
    }
}

echo "\n4. Admin route configuration:\n";
if (file_exists('routes/admin.php')) {
    $content = file_get_contents('routes/admin.php');
    echo "   Middleware: " . (strpos($content, "'web', 'auth', 'admin'") !== false ? "✅ Correct" : "❌ Issue") . "\n";
    echo "   Prefix: " . (strpos($content, "prefix('admin')") !== false ? "✅ Correct" : "❌ Missing") . "\n";
    echo "   Name: " . (strpos($content, "name('admin.')") !== false ? "✅ Correct" : "❌ Missing") . "\n";
    echo "   Controller: " . (strpos($content, "AdminDashboardController") !== false ? "✅ Correct" : "❌ Missing") . "\n";
}

echo "\n5. Next steps:\n";
echo "   - Test /test-route first to confirm basic routing works\n";
echo "   - If /test-route works but /admin doesn't, issue is in admin routes\n";
echo "   - Check Laravel logs: storage/logs/laravel.log\n";
echo "   - Make sure you're logged in as admin user for /admin\n";

echo "\n=== TROUBLESHOOTING COMPLETE ===\n";
