<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$request = Illuminate\Http\Request::create('/api/users', 'GET');
$response = $app->handle($request);
echo "UserController Response:\n";
echo $response->getContent();
