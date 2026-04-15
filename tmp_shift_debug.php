<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('role', 'karyawan')->first();
if (!$user) {
    echo "no karyawan\n";
    exit(0);
}

echo "User {$user->id} {$user->name} kantor={$user->kantor_id}\n";
foreach ($user->shifts as $shift) {
    echo "shift {$shift->id} {$shift->nama} hari={$shift->pivot->hari} tipe={$shift->pivot->tipe} kantor={$shift->pivot->kantor_id}\n";
}
$day = Carbon\Carbon::now()->format('l');
echo "today {$day}\n";
$sh = $user->shiftForDay($day, $user->kantor_id);
if ($sh) {
    echo "found {$sh->id} {$sh->nama} pivot hari={$sh->pivot->hari} kantor={$sh->pivot->kantor_id} tipe={$sh->pivot->tipe}\n";
} else {
    echo "no shift found\n";
}
