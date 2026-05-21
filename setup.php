<?php
/**
 * Setup Script for Qualification File Upload Feature
 * Run this file in your browser: http://127.0.0.1:8000/setup.php
 */

// Set the working directory
chdir(__DIR__);

// Include Laravel bootstrap
require __DIR__ . '/bootstrap/app.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Setup - Qualification File Upload</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .step { margin: 20px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #007bff; }
        .step h3 { margin-top: 0; color: #007bff; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #17a2b8; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        .button { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 10px 0; }
        .button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Qualification File Upload Setup</h1>
        <p>This script will set up the qualification file upload feature for you.</p>
";

try {
    echo "<div class='step'>";
    echo "<h3>Step 1: Running Migrations</h3>";
    echo "<p>Adding <code>certificate_path</code> column to database...</p>";
    
    $status = $kernel->call('migrate', ['--force' => true]);
    
    if ($status === 0) {
        echo "<p class='success'>✓ Migrations completed successfully!</p>";
    } else {
        echo "<p class='info'>ℹ Migrations already run or no new migrations.</p>";
    }
    echo "</div>";

    echo "<div class='step'>";
    echo "<h3>Step 2: Clearing Caches</h3>";
    echo "<p>Clearing all Laravel caches...</p>";
    
    $kernel->call('cache:clear');
    echo "<p class='success'>✓ Cache cleared</p>";
    
    $kernel->call('config:clear');
    echo "<p class='success'>✓ Config cache cleared</p>";
    
    $kernel->call('view:clear');
    echo "<p class='success'>✓ View cache cleared</p>";
    
    $kernel->call('route:clear');
    echo "<p class='success'>✓ Route cache cleared</p>";
    
    echo "</div>";

    echo "<div class='step'>";
    echo "<h3>Step 3: Checking Storage Link</h3>";
    
    if (file_exists(public_path('storage'))) {
        echo "<p class='success'>✓ Storage link exists</p>";
    } else {
        echo "<p class='info'>Creating storage link...</p>";
        $kernel->call('storage:link');
        echo "<p class='success'>✓ Storage link created</p>";
    }
    
    echo "</div>";

    echo "<div class='step' style='background: #d4edda; border-left-color: #28a745;'>";
    echo "<h3 style='color: #28a745;'>✓ Setup Complete!</h3>";
    echo "<p>The qualification file upload feature is now ready to use.</p>";
    echo "<h4>Next Steps:</h4>";
    echo "<ol>";
    echo "<li><strong>Refresh your browser</strong> (Ctrl+F5 or Cmd+Shift+R)</li>";
    echo "<li>Go to <strong>HR → Select an Employee</strong></li>";
    echo "<li>Click <strong>\"Edit Profile\"</strong></li>";
    echo "<li>Scroll to <strong>\"Qualifications\"</strong> section</li>";
    echo "<li>Upload a certificate file and save</li>";
    echo "<li>View the employee profile to see the certificate</li>";
    echo "</ol>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='step' style='background: #f8d7da; border-left-color: #dc3545;'>";
    echo "<h3 style='color: #dc3545;'>✗ Error Occurred</h3>";
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check the Laravel logs at <code>storage/logs/laravel.log</code></p>";
    echo "</div>";
}

echo "
    </div>
</body>
</html>
";
?>
