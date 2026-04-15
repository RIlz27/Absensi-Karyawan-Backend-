<?php
require 'vendor/autoload.php';
use Carbon\Carbon;

$jadwal = Carbon::createFromFormat('H:i:s', '06:00:00');
$aktual = Carbon::createFromFormat('H:i:s', '06:15:00');

$selisih = $jadwal->diffInMinutes($aktual, false);
echo "Selisih (06:00 to 06:15): " . $selisih . "\n";

$aktual2 = Carbon::createFromFormat('H:i:s', '05:50:00');
$selisih2 = $jadwal->diffInMinutes($aktual2, false);
echo "Selisih (06:00 to 05:50): " . $selisih2 . "\n";
