<?php
/**
 * Diagnostic Script - Check Certificate Upload Status
 * Access via: http://127.0.0.1:8000/diagnose-certificates.php
 */

$basePath = __DIR__;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Certificate Upload Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .check { margin: 15px 0; padding: 10px; border-left: 4px solid #007bff; background: #f9f9f9; }
        .check.success { border-left-color: #28a745; background: #f0f8f5; }
        .check.error { border-left-color: #dc3545; background: #fdf8f8; }
        .check.warning { border-left-color: #ffc107; background: #fffbf0; }
        .check h3 { margin: 0 0 5px 0; color: #333; }
        .check p { margin: 5px 0; color: #666; font-size: 14px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #007bff; }
        code { background: #f0f0f0; padding: 2px 5px; border-radius: 3px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        table th, table td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        table th { background: #f0f0f0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Certificate Upload Diagnostic</h1>
        <p>Checking your certificate upload setup...</p>

<?php

// Check 1: Database Migration
echo '<div class="check">';
echo '<h3>1️⃣ Database Migration</h3>';

try {
    // Try to connect to database
    $env_file = $basePath . '/.env';
    $env_content = file_get_contents($env_file);
    
    // Parse .env file
    preg_match('/DB_CONNECTION=(\w+)/', $env_content, $db_type);
    preg_match('/DB_HOST=([^\n]+)/', $env_content, $db_host);
    preg_match('/DB_DATABASE=([^\n]+)/', $env_content, $db_name);
    preg_match('/DB_USERNAME=([^\n]+)/', $env_content, $db_user);
    preg_match('/DB_PASSWORD=([^\n]*)/', $env_content, $db_pass);
    
    $db_type = $db_type[1] ?? 'unknown';
    $db_host = trim($db_host[1] ?? 'unknown');
    $db_name = trim($db_name[1] ?? 'unknown');
    $db_user = trim($db_user[1] ?? 'unknown');
    
    echo '<p>Database Type: <code>' . htmlspecialchars($db_type) . '</code></p>';
    echo '<p>Database Host: <code>' . htmlspecialchars($db_host) . '</code></p>';
    echo '<p>Database Name: <code>' . htmlspecialchars($db_name) . '</code></p>';
    
    // Check if migration file exists
    $migration_file = $basePath . '/database/migrations/2024_01_16_000000_add_certificate_to_employee_qualifications.php';
    if (file_exists($migration_file)) {
        echo '<p class="success">✓ Migration file exists</p>';
    } else {
        echo '<p class="error">✗ Migration file not found</p>';
    }
    
} catch (Exception $e) {
    echo '<p class="error">✗ Error checking database: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

echo '</div>';

// Check 2: Storage Directory
echo '<div class="check">';
echo '<h3>2️⃣ Storage Directory</h3>';

$storage_dir = $basePath . '/storage/app/public/qualifications';
if (is_dir($storage_dir)) {
    echo '<p class="success">✓ Qualifications directory exists</p>';
    echo '<p>Path: <code>' . htmlspecialchars($storage_dir) . '</code></p>';
    
    if (is_writable($storage_dir)) {
        echo '<p class="success">✓ Directory is writable</p>';
    } else {
        echo '<p class="error">✗ Directory is NOT writable</p>';
        echo '<p>Fix: Run <code>icacls storage /grant:r "%username%":F /t</code></p>';
    }
    
    // Count files
    $files = glob($storage_dir . '/*/*');
    echo '<p>Uploaded files: <strong>' . count($files) . '</strong></p>';
    
    if (count($files) > 0) {
        echo '<p class="success">✓ Files are being stored</p>';
        echo '<table>';
        echo '<tr><th>File</th><th>Size</th><th>Modified</th></tr>';
        foreach (array_slice($files, 0, 10) as $file) {
            $size = filesize($file);
            $modified = date('Y-m-d H:i:s', filemtime($file));
            echo '<tr>';
            echo '<td><code>' . htmlspecialchars(basename($file)) . '</code></td>';
            echo '<td>' . number_format($size / 1024, 2) . ' KB</td>';
            echo '<td>' . $modified . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p class="warning">⚠ No files found in storage directory</p>';
    }
} else {
    echo '<p class="error">✗ Qualifications directory does NOT exist</p>';
    echo '<p>Path: <code>' . htmlspecialchars($storage_dir) . '</code></p>';
    echo '<p>Fix: Create the directory manually or run setup again</p>';
}

echo '</div>';

// Check 3: Storage Link
echo '<div class="check">';
echo '<h3>3️⃣ Storage Link</h3>';

$storage_link = $basePath . '/public/storage';
if (is_link($storage_link)) {
    echo '<p class="success">✓ Storage link exists (symbolic link)</p>';
    $target = readlink($storage_link);
    echo '<p>Target: <code>' . htmlspecialchars($target) . '</code></p>';
} else if (is_dir($storage_link)) {
    echo '<p class="warning">⚠ Storage link exists but is a directory (not a symbolic link)</p>';
    echo '<p>This might work but is not ideal. Consider recreating as a symbolic link.</p>';
} else {
    echo '<p class="error">✗ Storage link does NOT exist</p>';
    echo '<p>Fix: Run <code>php artisan storage:link</code></p>';
}

echo '</div>';

// Check 4: Model
echo '<div class="check">';
echo '<h3>4️⃣ Model Methods</h3>';

$model_file = $basePath . '/app/Models/EmployeeQualification.php';
if (file_exists($model_file)) {
    $model_content = file_get_contents($model_file);
    
    if (strpos($model_content, 'getCertificateUrl') !== false) {
        echo '<p class="success">✓ getCertificateUrl() method exists</p>';
    } else {
        echo '<p class="error">✗ getCertificateUrl() method NOT found</p>';
    }
    
    if (strpos($model_content, 'getCertificateFileName') !== false) {
        echo '<p class="success">✓ getCertificateFileName() method exists</p>';
    } else {
        echo '<p class="error">✗ getCertificateFileName() method NOT found</p>';
    }
    
    if (strpos($model_content, 'certificate_path') !== false) {
        echo '<p class="success">✓ certificate_path field is referenced</p>';
    } else {
        echo '<p class="error">✗ certificate_path field NOT referenced</p>';
    }
} else {
    echo '<p class="error">✗ Model file not found</p>';
}

echo '</div>';

// Check 5: View
echo '<div class="check">';
echo '<h3>5️⃣ View Files</h3>';

$show_file = $basePath . '/resources/views/pages/hr/show.blade.php';
if (file_exists($show_file)) {
    $show_content = file_get_contents($show_file);
    
    if (strpos($show_content, 'certificate_path') !== false) {
        echo '<p class="success">✓ show.blade.php references certificate_path</p>';
    } else {
        echo '<p class="error">✗ show.blade.php does NOT reference certificate_path</p>';
    }
    
    if (strpos($show_content, 'getCertificateFileName') !== false) {
        echo '<p class="success">✓ show.blade.php uses getCertificateFileName()</p>';
    } else {
        echo '<p class="error">✗ show.blade.php does NOT use getCertificateFileName()</p>';
    }
} else {
    echo '<p class="error">✗ show.blade.php not found</p>';
}

$edit_file = $basePath . '/resources/views/pages/hr/profile_edit.blade.php';
if (file_exists($edit_file)) {
    $edit_content = file_get_contents($edit_file);
    
    if (strpos($edit_content, 'qualifications') !== false) {
        echo '<p class="success">✓ profile_edit.blade.php has qualifications form</p>';
    } else {
        echo '<p class="error">✗ profile_edit.blade.php does NOT have qualifications form</p>';
    }
} else {
    echo '<p class="error">✗ profile_edit.blade.php not found</p>';
}

echo '</div>';

// Check 6: Controller
echo '<div class="check">';
echo '<h3>6️⃣ Controller Methods</h3>';

$controller_file = $basePath . '/app/Http/Controllers/SupportTeam/HRController.php';
if (file_exists($controller_file)) {
    $controller_content = file_get_contents($controller_file);
    
    if (strpos($controller_content, 'updateQualifications') !== false) {
        echo '<p class="success">✓ updateQualifications() method exists</p>';
    } else {
        echo '<p class="error">✗ updateQualifications() method NOT found</p>';
    }
    
    if (strpos($controller_content, 'certificate') !== false) {
        echo '<p class="success">✓ Controller handles certificate uploads</p>';
    } else {
        echo '<p class="error">✗ Controller does NOT handle certificate uploads</p>';
    }
} else {
    echo '<p class="error">✗ Controller file not found</p>';
}

echo '</div>';

// Check 7: Logs
echo '<div class="check">';
echo '<h3>7️⃣ Recent Errors</h3>';

$log_file = $basePath . '/storage/logs/laravel.log';
if (file_exists($log_file)) {
    $log_content = file_get_contents($log_file);
    $lines = array_reverse(explode("\n", $log_content));
    
    $errors = [];
    foreach (array_slice($lines, 0, 50) as $line) {
        if (strpos($line, 'ERROR') !== false || strpos($line, 'Exception') !== false) {
            $errors[] = $line;
        }
    }
    
    if (count($errors) > 0) {
        echo '<p class="warning">⚠ Found ' . count($errors) . ' recent errors</p>';
        echo '<p>Last error:</p>';
        echo '<pre style="background: #f0f0f0; padding: 10px; overflow-x: auto;">' . htmlspecialchars($errors[0]) . '</pre>';
    } else {
        echo '<p class="success">✓ No recent errors in logs</p>';
    }
} else {
    echo '<p class="warning">⚠ Log file not found</p>';
}

echo '</div>';

// Summary
echo '<div style="margin-top: 30px; padding: 15px; background: #e7f3ff; border: 1px solid #007bff; border-radius: 5px;">';
echo '<h3>📋 Summary</h3>';
echo '<p>If you see errors above, here are the fixes:</p>';
echo '<ul>';
echo '<li><strong>Migration not run:</strong> Run <code>php artisan migrate --force</code></li>';
echo '<li><strong>Storage directory missing:</strong> Run setup again or create manually</li>';
echo '<li><strong>Storage link missing:</strong> Run <code>php artisan storage:link</code></li>';
echo '<li><strong>Directory not writable:</strong> Run <code>icacls storage /grant:r "%username%":F /t</code></li>';
echo '<li><strong>No files in storage:</strong> Check if files are actually being uploaded</li>';
echo '</ul>';
echo '</div>';

?>

    </div>
</body>
</html>
