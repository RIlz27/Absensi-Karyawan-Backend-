<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tables = DB::select('SHOW TABLES');
foreach ($tables as $table) {
    $tableName = array_values((array)$table)[0];
    if (str_contains($tableName, 'point') || str_contains($tableName, 'token') || str_contains($tableName, 'item') || str_contains($tableName, 'reward')) {
        echo "Table: $tableName" . PHP_EOL;
        $columns = DB::select("DESCRIBE $tableName");
        foreach ($columns as $column) {
            echo "  - " . $column->Field . " (" . $column->Type . ")" . PHP_EOL;
        }
        echo PHP_EOL;
    }
}
