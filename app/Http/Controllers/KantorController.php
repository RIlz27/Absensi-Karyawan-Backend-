<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Kantor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KantorController extends Controller
{
    // app/Http/Controllers/KantorController.php

    public function index()
    {
        return response()->json(Kantor::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama'         => 'required|string',
            'alamat'       => 'required|string',
            'latitude'     => 'required|numeric',
            'longitude'    => 'required|numeric',
            'radius_meter' => 'required|integer',
        ]);

        $kantor = Kantor::create($data);
        return response()->json(['message' => 'Kantor Berhasil Ditambah', 'data' => $kantor]);
    }

    public function update(Request $request, $id)
    {
        $kantor = Kantor::find($id);

        if (!$kantor) {
            return response()->json(['message' => 'Kantor tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'latitude' => 'required',
            'longitude' => 'required',
            'radius_meter' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $kantor->update($request->all());

        return response()->json([
            'message' => 'Kantor berhasil diupdate',
            'data' => $kantor
        ]);
    }

    public function destroy($id)
    {
        $kantor = Kantor::find($id);

        if (!$kantor) {
            return response()->json(['message' => 'Kantor tidak ditemukan'], 404);
        }

        $kantor->delete();

        return response()->json(['message' => 'Kantor berhasil dihapus']);
    }

    public function generateQr(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kantor_id' => 'required|exists:kantors,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $qrCodeString = "ABSEN-" . $request->kantor_id . "-" . time();

        return response()->json([
            'success' => true,
            'message' => 'QR Code berhasil di-generate',
            'qr_string' => $qrCodeString,
            'kantor_id' => $request->kantor_id,
            'generated_at' => now()
        ]);
    }
}
