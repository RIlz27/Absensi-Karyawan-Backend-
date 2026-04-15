<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PointRule;
use App\Models\FlexibilityItem;
use App\Models\PointLedger;
use App\Models\UserToken;

class AdminGamificationController extends Controller
{
    // ==========================================
    // 1. MANAJEMEN POINT RULES (FULL CRUD)
    // ==========================================
    public function getRules() {
        return response()->json(PointRule::latest()->get());
    }

    public function storeRule(Request $request) {
        $validated = $request->validate([
            'rule_name' => 'required|string',
            'target_role' => 'required|string',
            'condition_operator' => 'required|string',
            'condition_value' => 'required|string',
            'point_modifier' => 'required|integer',
        ]);
        $rule = PointRule::create($validated);
        return response()->json(['message' => 'Aturan berhasil dibuat', 'data' => $rule]);
    }

    public function updateRule(Request $request, $id) {
        $rule = PointRule::findOrFail($id);
        $rule->update($request->all());
        return response()->json(['message' => 'Aturan berhasil diupdate', 'data' => $rule]);
    }

    public function destroyRule($id) {
        PointRule::findOrFail($id)->delete();
        return response()->json(['message' => 'Aturan berhasil dihapus']);
    }

    // ==========================================
    // 2. MANAJEMEN KATALOG TOKO (FULL CRUD)
    // ==========================================
    public function getItems() {
        return response()->json(FlexibilityItem::latest()->get());
    }

    public function storeItem(Request $request) {
        $validated = $request->validate([
            'item_name' => 'required|string',
            'point_cost' => 'required|integer',
            'stock_limit' => 'nullable|integer',
        ]);
        $item = FlexibilityItem::create($validated);
        return response()->json(['message' => 'Item berhasil ditambahkan', 'data' => $item]);
    }

    public function updateItem(Request $request, $id) {
        $item = FlexibilityItem::findOrFail($id);
        $item->update($request->all());
        return response()->json(['message' => 'Item berhasil diupdate', 'data' => $item]);
    }

    public function destroyItem($id) {
        FlexibilityItem::findOrFail($id)->delete();
        return response()->json(['message' => 'Item berhasil dihapus']);
    }

    // ==========================================
    // 3. AUDIT TRAIL / BUKU MUTASI (READ ONLY)
    // ==========================================
    public function getLedgers() {
        // Pake "with('user')" biar nama karyawannya ikut kepanggil, bukan cuma ID-nya doang
        $ledgers = PointLedger::with('user')->latest()->get();
        return response()->json($ledgers);
    }

    // ==========================================
    // 4. MANAJEMEN INVENTORY KARYAWAN 
    // ==========================================
    public function getTokens() {
        // Narik semua token karyawan beserta detail user dan barangnya
        $tokens = UserToken::with(['user', 'item'])->latest()->get();
        return response()->json($tokens);
    }

    public function markTokenUsed($id) {
        $token = UserToken::findOrFail($id);
        
        // Cek kalau vouchernya emang belum dipake
        if ($token->status === 'USED') {
            return response()->json(['message' => 'Voucher ini sudah pernah digunakan!'], 400);
        }

        $token->update(['status' => 'USED']);
        return response()->json(['message' => 'Voucher berhasil digunakan / di-redeem!']);
    }

    // ==========================================
    // 5. MANUAL POINT ADJUSTMENT (KASIR POIN)
    // ==========================================
    public function manualPointAdjustment(Request $request) {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|integer',
            'reason' => 'required|string|max:255',
        ]);

        $user = \App\Models\User::findOrFail($validated['user_id']);
        $saldoSekarang = $user->current_points;
        $amount = (int) $validated['amount'];

        \App\Models\PointLedger::create([
            'user_id' => $user->id,
            'transaction_type' => $amount > 0 ? 'EARN' : 'PENALTY',
            'amount' => $amount,
            'current_balance' => $saldoSekarang + $amount,
            'description' => "Penyesuaian Manual: " . $validated['reason'],
        ]);

        return response()->json([
            'message' => 'Poin berhasil disesuaikan secara manual!',
            'new_balance' => $saldoSekarang + $amount
        ]);
    }
}