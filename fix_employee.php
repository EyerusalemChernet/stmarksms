<?php
// Quick fix to add employee user
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

try {
    // Disable foreign key checks
    DB::statement('SET FOREIGN_KEY_CHECKS=0');
    
    // Delete existing employee user if exists
    DB::table('users')->where('username', 'employee')->delete();
    
    // Re-enable foreign key checks
    DB::statement('SET FOREIGN_KEY_CHECKS=1');
    
    // Insert employee user
    DB::table('users')->insert([
        'name'           => 'Demo Employee',
        'email'          => 'employee@stmarksms.com',
        'user_type'      => 'employee',
        'username'       => 'employee',
        'password'       => Hash::make('cj'),
        'code'           => strtoupper(Str::random(10)),
        'remember_token' => Str::random(10),
        'created_at'     => now(),
        'updated_at'     => now(),
    ]);
    
    echo "✓ Employee user created successfully!\n";
    echo "Username: employee\n";
    echo "Password: cj\n";
    
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
