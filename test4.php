<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$user = App\Models\User::whereNotNull('avatar')->first();
if ($user) {
    echo base64_encode(json_encode(['avatar' => $user->avatar]));
} else {
    echo base64_encode(json_encode(['avatar' => 'null']));
}
