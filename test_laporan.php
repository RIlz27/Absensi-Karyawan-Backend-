<?php
try {
    $ctrl = app(App\Http\Controllers\LaporanController::class);
    $r = request();
    $ctrl->getStatistik($r);
    $ctrl->getPeringkat($r);
    $ctrl->getLaporanHarian($r);
    $ctrl->getLaporanBulanan($r);
    echo "ALL_SUCCESS\n";
}
catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . " on line " . $e->getLine() . " in " . $e->getFile() . "\n";
}
