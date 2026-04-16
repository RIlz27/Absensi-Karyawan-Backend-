<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\PointLedger;
use App\Models\FlexibilityItem;
use App\Models\UserToken;
use App\Models\Absensi;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

$userId = 1;
$user = User::find($userId);

echo "1. Giving 1000 points to " . $user->name . PHP_EOL;
DB::transaction(function() use ($user) {
    PointLedger::create([
        'user_id' => $user->id,
        'transaction_type' => 'EARN',
        'amount' => 1000,
        'current_balance' => $user->points + 1000,
        'description' => 'Test Seed Points'
    ]);
    $user->update(['points' => $user->points + 1000]);
});

echo "Points Now: " . $user->fresh()->points . PHP_EOL;

echo "2. Buying 'Bebas Terlambat 30 Menit'..." . PHP_EOL;
$item = FlexibilityItem::where('item_name', 'Bebas Terlambat 30 Menit')->first();

// Simulate GamificationController@buyItem
DB::transaction(function() use ($user, $item) {
    $saldoBaru = $user->fresh()->points - $item->point_cost;
    PointLedger::create([
        'user_id' => $user->id,
        'transaction_type' => 'SPEND',
        'amount' => -$item->point_cost,
        'current_balance' => $saldoBaru,
        'description' => "Membeli item: {$item->item_name}"
    ]);
    UserToken::create([
        'user_id' => $user->id,
        'item_id' => $item->id,
        'status' => 'AVAILABLE'
    ]);
    $user->update(['points' => $saldoBaru]);
});

echo "Points Now: " . $user->fresh()->points . PHP_EOL;
$token = UserToken::where('user_id', $user->id)->where('status', 'AVAILABLE')->first();
echo "Token Owned: " . ($token ? "YES (Item: " . $token->item->item_name . ")" : "NO") . PHP_EOL;

echo "3. Simulating LATE Attendance (20 mins late)..." . PHP_EOL;
$shift = Shift::first();
$jadwalMasuk = Carbon::parse($shift->jam_masuk);
$waktuAbsen = $jadwalMasuk->copy()->addMinutes(20);

// Create fake absensi
$absensi = Absensi::create([
    'user_id' => $user->id,
    'shift_id' => $shift->id,
    'kantor_id' => 1,
    'tanggal' => Carbon::now()->toDateString(),
    'jam_masuk' => $waktuAbsen,
    'status' => 'Terlambat',
    'metode' => 'QR',
    'latitude' => -6.2,
    'longitude' => 106.8
]);

echo "Absensi Status BEFORE point check: " . $absensi->status . PHP_EOL;

// 4. Run processAbsensiPoints logic (via Reflection or just mock call)
// Since it's private in AbsensiController, I'll use Reflection
$controller = new \App\Http\Controllers\AbsensiController();
$method = new ReflectionMethod($controller, 'processAbsensiPoints');
$method->setAccessible(true);
$results = $method->invoke($controller, $absensi, $user->fresh());

echo "Absensi Status AFTER point check: " . $absensi->fresh()->status . PHP_EOL;

$tokenFresh = $token->fresh();
echo "Token Status AFTER: " . $tokenFresh->status . PHP_EOL;
echo "Token used_at_attendance_id: " . $tokenFresh->used_at_attendance_id . PHP_EOL;

if ($absensi->fresh()->status === 'Hadir Tepat Waktu (Token Used)' && $tokenFresh->status === 'USED') {
    echo "SUCCESS: Token Interceptor Working!" . PHP_EOL;
} else {
    echo "FAILED: Token Interceptor NOT Working!" . PHP_EOL;
}
