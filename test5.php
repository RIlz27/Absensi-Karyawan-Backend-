<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = new \Illuminate\Http\Request();
$request->replace(['user_ids' => [1], 'shift_id' => 1, 'kantor_id' => 1]);
$controller = app()->make(\App\Http\Controllers\api\UserShiftController::class);
echo $controller->storeShiftBiasa($request)->getContent();
