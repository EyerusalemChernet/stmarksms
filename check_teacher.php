<?php
// Quick script to check and fix teacher login credentials
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Find by username since email lookup failed but username exists
$u = \App\User::where('username', 'teacher')->first();
if ($u) {
    echo "Found user by username 'teacher':\n";
    echo "  Name: " . $u->name . "\n";
    echo "  Email: " . $u->email . "\n";
    echo "  User type: " . $u->user_type . "\n";
    echo "  Username: " . $u->username . "\n";

    $passOk = \Illuminate\Support\Facades\Hash::check('stmark', $u->password);
    echo "  Password 'stmark' matches: " . ($passOk ? "YES" : "NO") . "\n";

    // Fix: set email to teacher@stmarksms.com and reset password
    $u->email = 'teacher@stmarksms.com';
    $u->password = \Illuminate\Support\Facades\Hash::make('stmark');
    $u->save();
    echo "\nFIXED: Email set to teacher@stmarksms.com, password reset to 'stmark'\n";

    // Verify
    $u->refresh();
    $check = \Illuminate\Support\Facades\Hash::check('stmark', $u->password);
    echo "Verify: " . ($check ? "OK - Password works now!" : "STILL BROKEN") . "\n";
} else {
    echo "No user with username 'teacher' found either.\n";

    // Check all teacher-type users
    $teachers = \App\User::where('user_type', 'teacher')->get();
    echo "Teacher-type users in DB: " . $teachers->count() . "\n";
    foreach ($teachers as $t) {
        echo "  - ID: {$t->id} | {$t->name} | {$t->email} | username: {$t->username}\n";
    }
}
