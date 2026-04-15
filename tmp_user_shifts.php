<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rows = Illuminate\Support\Facades\DB::table('user_shifts')->get();
echo 'user_shifts count=' . count($rows) . "\n";
foreach ($rows as $r) {
    echo "id={$r->id} user={$r->user_id} shift={$r->shift_id} hari={$r->hari} tipe={$r->tipe} kantor={$r->kantor_id}\n";
}
