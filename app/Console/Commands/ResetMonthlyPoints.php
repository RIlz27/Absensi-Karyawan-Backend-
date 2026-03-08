<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\PointHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResetMonthlyPoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reset-monthly-points';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Archive current user points and reset them to 0 (Runs monthly)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Because this runs on the 1st of the month at 00:00, 
        // the points belong to the previous month.
        $lastMonth = Carbon::now()->subMonth();
        $month = $lastMonth->month;
        $year = $lastMonth->year;

        $this->info("Starting Point Archival for {$month}/{$year}...");

        try {
            DB::beginTransaction();

            $users = User::where('role', 'karyawan')->where('is_active', true)->get();
            $count = 0;

            foreach ($users as $user) {
                // 1. Archive the points
                PointHistory::create([
                    'user_id' => $user->id,
                    'month' => $month,
                    'year' => $year,
                    'final_score' => $user->points,
                ]);

                // 2. Reset points to 0
                $user->update(['points' => 0]);
                $count++;
            }

            DB::commit();
            $this->info("Successfully archived and reset points for {$count} users.");
            Log::info("Monthly Point Reset completed for {$count} users (Period: {$month}/{$year}).");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Failed to reset points: " . $e->getMessage());
            Log::error("Monthly Point Reset FAILED: " . $e->getMessage());
        }
    }
}
