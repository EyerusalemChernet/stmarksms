<?php
$user = App\User::where('email', 'parent@stmarksms.com')->first();
Auth::login($user);
$controller = app()->make('App\Http\Controllers\MyParent\MyController');
echo 'Dashboard returned: ' . get_class($controller->dashboard());
