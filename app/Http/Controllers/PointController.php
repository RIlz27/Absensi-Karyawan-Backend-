<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\PointHistory;

class PointController extends Controller
{
    /**
     * Get the current monthly leaderboard.
     * Accessible by Karyawan, Manager, and Admin.
     */
    public function getLeaderboard(Request $request)
    {
        $limit = $request->query('limit', 10);
        $users = User::where('role', 'karyawan')
            ->where('is_active', true)
            ->orderBy('points', 'desc')
            ->select('id', 'name', 'avatar', 'points', 'kantor_id')
            ->with('kantor:id,name')
            ->take($limit)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $users
        ]);
    }

    /**
     * Manually adjust a user's points (e.g. Penalty or Bonus).
     * Accessible by Admin / Manager only.
     */
    public function updatePoints(Request $request, $id)
    {
        $request->validate([
            'adjust_amount' => 'required|integer', // Can be positive or negative
            'reason' => 'required|string|max:255'
        ]);

        $user = User::findOrFail($id);

        if ($user->role !== 'karyawan') {
            return response()->json(['message' => 'Only Karyawan points can be adjusted.'], 400);
        }

        $newPoints = max(0, $user->points + $request->adjust_amount);
        $user->update(['points' => $newPoints]);

        // Optional: We could log the string 'reason' if we had a point_adjustments table,
        // but for now adjusting the user points is sufficient per spec.

        return response()->json([
            'status' => 'success',
            'message' => 'Points adjusted successfully.',
            'data' => [
                'user_id' => $user->id,
                'name' => $user->name,
                'old_points' => $user->points - $request->adjust_amount,
                'new_points' => $user->points
            ]
        ]);
    }
}
