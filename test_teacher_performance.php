<?php
$user = App\User::where('user_type', 'teacher')->first();
Auth::login($user);
$request = Request::create('/my/performance', 'GET');
$response = app()->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
if ($response->isRedirect()) {
    echo "Redirect: " . $response->headers->get('Location') . "\n";
}
