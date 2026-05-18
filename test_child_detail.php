<?php
$user = App\User::where('email', 'parent@stmarksms.com')->first();
Auth::login($user);
$controller = app()->make('App\Http\Controllers\MyParent\MyController');

$studentRepo = app()->make('App\Repositories\StudentRepo');
$children = $studentRepo->getRecord(['my_parent_id' => $user->id])->get();

if ($children->isEmpty()) {
    echo "No children found for parent\n";
} else {
    $child = $children->first();
    echo "Child found: " . $child->user_id . "\n";
    echo "Testing childDetail: " . get_class($controller->childDetail($child->user_id)) . "\n";
    echo "Testing timeline: " . get_class($controller->timeline($child->user_id)) . "\n";
}
