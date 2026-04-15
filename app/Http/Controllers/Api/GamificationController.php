<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FlexibilityItem;
use App\Models\UserToken;
use App\Models\PointLedger;
use Illuminate\Support\Facades\DB;

class GamificationController extends Controller
{
    /**
     * 1. Lihat Saldo & Riwayat Poin
     */
    public function getPointStatus(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status' => 'success',
            'data' => [
                'current_balance' => $user->current_points,
                'history' => $user->pointLedgers()->latest('id')->take(10)->get()
            ]
        ]);
    }

    /**
     * 2. Lihat Katalog Item (Toko)
     */
    public function getStoreItems()
    {
        $items = FlexibilityItem::all();

        return response()->json([
            'status' => 'success',
            'data' => $items
        ]);
    }

    /**
     * 3. Beli Item Pake Poin (SPEND)
     */
    public function buyItem(Request $request, $itemId)
    {
        $user = $request->user();
        $item = FlexibilityItem::findOrFail($itemId);

        // Validasi 1: Cek Saldo
        if ($user->current_points < $item->point_cost) {
            return response()->json(['message' => 'Maaf, Poin Anda tidak mencukupi'], 400);
        }

        // Pake Database Transaction biar aman
        DB::beginTransaction();
        try {
            $saldoBaru = $user->current_points - $item->point_cost;

            // Potong saldo (Masuk ke Ledger)
            PointLedger::create([
                'user_id' => $user->id,
                'transaction_type' => 'SPEND',
                'amount' => -$item->point_cost,
                'current_balance' => $saldoBaru,
                'description' => "Membeli item: {$item->item_name}"
            ]);

            // Kasih barangnya (Masuk ke Inventory)
            UserToken::create([
                'user_id' => $user->id,
                'item_id' => $item->id,
                'status' => 'AVAILABLE'
            ]);

            $user->current_points = $saldoBaru;
            $user->save();

            DB::commit();
            return response()->json(['message' => 'Berhasil membeli item!']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Terjadi kesalahan sistem'], 500);
        }
    }

    /**
     * 4. Lihat Inventory (Token yang dimiliki)
     */
    public function getMyTokens(Request $request)
    {
        $tokens = UserToken::with('item')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $tokens
        ]);
    }
}
