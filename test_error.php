<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $request = Illuminate\Http\Request::create('/api/laporan/harian?tanggal=2026-03-11', 'GET');
    $controller = app()->make(App\Http\Controllers\LaporanController::class);
    $response = $controller->getLaporanHarian($request);
    echo json_encode($response->getData());
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getFile() . ':' . $e->getLine() . "\n";
    echo $e->getTraceAsString();
}
