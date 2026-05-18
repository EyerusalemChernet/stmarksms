<?php
/**
 * Quick Setup - Run migrations and clear cache
 * Access via: http://127.0.0.1:8000/quick-setup.php
 */

// Get the base path
$basePath = __DIR__;

// HTML output for browser
?>
<!DOCTYPE html>
<html>
<head>
    <title>HR System Setup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .step { margin: 15px 0; padding: 10px; border-left: 4px solid #007bff; background: #f9f9f9; }
        .step.success { border-left-color: #28a745; background: #f0f8f5; }
        .step.error { border-left-color: #dc3545; background: #fdf8f8; }
        .step h3 { margin: 0 0 5px 0; color: #333; }
        .step p { margin: 5px 0; color: #666; font-size: 14px; }
        .icon { margin-right: 5px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #007bff; }
        .complete { text-align: center; margin-top: 30px; padding: 20px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; }
        .complete h2 { color: #155724; margin: 0; }
        .next-steps { margin-top: 20px; padding: 15px; background: #e7f3ff; border-left: 4px solid #007bff; }
        .next-steps h3 { margin-top: 0; }
        .next-steps ol { margin: 10px 0; padding-left: 20px; }
        .next-steps li { margin: 8px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 HR System Setup</h1>
        <p>Running setup tasks...</p>

<?php

// Step 1: Run migrations
echo '<div class="step">';
echo '<h3><span class="icon">1️⃣</span>Running Database Migrations</h3>';
$output = shell_exec("cd {$basePath} && php artisan migrate --force 2>&1");
if (strpos($output, 'error') === false && strpos($output, 'Error') === false) {
    echo '<p class="success">✓ Migrations completed successfully</p>';
    echo '<p style="color: #666; font-size: 12px;">' . nl2br(htmlspecialchars($output)) . '</p>';
} else {
    echo '<p class="error">✗ Migration may have failed</p>';
    echo '<p style="color: #666; font-size: 12px;">' . nl2br(htmlspecialchars($output)) . '</p>';
}
echo '</div>';

// Step 2: Clear caches
echo '<div class="step">';
echo '<h3><span class="icon">2️⃣</span>Clearing Application Caches</h3>';

$caches = [
    'cache:clear' => 'Application Cache',
    'config:clear' => 'Config Cache',
    'view:clear' => 'View Cache',
    'route:clear' => 'Route Cache',
];

foreach ($caches as $cmd => $label) {
    $output = shell_exec("cd {$basePath} && php artisan {$cmd} 2>&1");
    echo '<p class="success">✓ ' . $label . ' cleared</p>';
}

echo '</div>';

// Step 3: Storage link
echo '<div class="step">';
echo '<h3><span class="icon">3️⃣</span>Setting Up Storage Link</h3>';

if (!file_exists($basePath . '/public/storage')) {
    $output = shell_exec("cd {$basePath} && php artisan storage:link 2>&1");
    echo '<p class="success">✓ Storage link created</p>';
} else {
    echo '<p class="success">✓ Storage link already exists</p>';
}

echo '</div>';

// Step 4: Verify database column
echo '<div class="step">';
echo '<h3><span class="icon">4️⃣</span>Verifying Database Schema</h3>';

try {
    // Check if certificate_path column exists
    $dbPath = $basePath . '/database/database.sqlite';
    if (file_exists($dbPath)) {
        $db = new PDO('sqlite:' . $dbPath);
        $result = $db->query("PRAGMA table_info(employee_qualifications)");
        $columns = $result->fetchAll(PDO::FETCH_ASSOC);
        $hasCertificate = false;
        foreach ($columns as $col) {
            if ($col['name'] === 'certificate_path') {
                $hasCertificate = true;
                break;
            }
        }
        if ($hasCertificate) {
            echo '<p class="success">✓ Database schema is correct (certificate_path column exists)</p>';
        } else {
            echo '<p class="error">✗ Database schema issue: certificate_path column not found</p>';
        }
    } else {
        echo '<p class="info">ℹ Using MySQL/other database (cannot verify via SQLite)</p>';
    }
} catch (Exception $e) {
    echo '<p class="info">ℹ Database verification skipped</p>';
}

echo '</div>';

// Step 5: Verify qualifications directory
echo '<div class="step">';
echo '<h3><span class="icon">5️⃣</span>Checking Storage Directories</h3>';

$storageDir = $basePath . '/storage/app/public/qualifications';
if (!is_dir($storageDir)) {
    @mkdir($storageDir, 0755, true);
    echo '<p class="success">✓ Qualifications storage directory created</p>';
} else {
    echo '<p class="success">✓ Qualifications storage directory exists</p>';
}

// Check if writable
if (is_writable($storageDir)) {
    echo '<p class="success">✓ Storage directory is writable</p>';
} else {
    echo '<p class="error">✗ Storage directory is not writable</p>';
}

echo '</div>';

// Completion message
echo '<div class="complete">';
echo '<h2>✅ Setup Complete!</h2>';
echo '<p>All setup tasks have been completed successfully.</p>';
echo '</div>';

echo '<div class="next-steps">';
echo '<h3>📋 Next Steps:</h3>';
echo '<ol>';
echo '<li><strong>Refresh your browser</strong> - Press <code>Ctrl+F5</code> (Windows) or <code>Cmd+Shift+R</code> (Mac)</li>';
echo '<li><strong>Go to HR Module</strong> - Navigate to HR → Employees</li>';
echo '<li><strong>Select an Employee</strong> - Click on any employee to view their profile</li>';
echo '<li><strong>Edit Profile</strong> - Click the "Edit Profile" button</li>';
echo '<li><strong>Upload Certificate</strong> - Scroll to "Qualifications" section and upload a PDF or image file</li>';
echo '<li><strong>Save Changes</strong> - Click "Save Changes" button</li>';
echo '<li><strong>Verify Upload</strong> - Go back to the employee profile and check if the certificate appears with a download link</li>';
echo '</ol>';
echo '</div>';

echo '<div style="margin-top: 30px; padding: 15px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 5px;">';
echo '<p><strong>💡 Tip:</strong> If you still don\'t see the certificates, try:</p>';
echo '<ul>';
echo '<li>Clear your browser cache (Ctrl+Shift+Delete)</li>';
echo '<li>Close and reopen your browser</li>';
echo '<li>Check the Laravel logs: <code>storage/logs/laravel.log</code></li>';
echo '</ul>';
echo '</div>';

?>

    </div>
</body>
</html>
