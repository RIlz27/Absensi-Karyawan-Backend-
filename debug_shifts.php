<?php

use App\Models\User;
use Carbon\Carbon;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = User::find(1);
echo "User: " . $user->name . PHP_EOL;
foreach ($user->shifts as $shift) {
    echo "Day: [" . $shift->pivot->hari . "]" . PHP_EOL;
}
