<?php

namespace App\Http\Controllers;

use App\Models\Pengumuman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PengumumanController extends Controller
{
    /**
     * Display a listing of active announcements for the User Dashboard (Hero Section)
     */
    public function index()
    {
        $pengumumans = Pengumuman::with('creator:id,name') // Only fetch id and name of creator
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json($pengumumans);
    }

    /**
     * Display all announcements for Admin/Manager Management
     */
    public function adminIndex()
    {
        // Must be admin or manager
        if (!in_array(Auth::user()->role, ['admin', 'manager'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $pengumumans = Pengumuman::with('creator:id,name')
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json($pengumumans);
    }

    /**
     * Store a newly created announcement.
     */
    public function store(Request $request)
    {
        if (!in_array(Auth::user()->role, ['admin', 'manager'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'required|in:Info,Urgent,Event',
            'is_active' => 'boolean'
        ]);

        $pengumuman = Pengumuman::create([
            'title' => $request->title,
            'content' => $request->content,
            'category' => $request->category,
            'is_active' => $request->has('is_active') ? $request->is_active : true,
            'created_by' => Auth::id()
        ]);

        return response()->json([
            'message' => 'Pengumuman berhasil dibuat',
            'data' => $pengumuman
        ], 201);
    }

    /**
     * Update the specified announcement.
     */
    public function update(Request $request, $id)
    {
        if (!in_array(Auth::user()->role, ['admin', 'manager'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $pengumuman = Pengumuman::findOrFail($id);

        $request->validate([
            'title' => 'string|max:255',
            'content' => 'string',
            'category' => 'in:Info,Urgent,Event',
            'is_active' => 'boolean'
        ]);

        $pengumuman->update($request->only(['title', 'content', 'category', 'is_active']));

        return response()->json([
            'message' => 'Pengumuman berhasil diupdate',
            'data' => $pengumuman
        ]);
    }

    /**
     * Remove the specified announcement.
     */
    public function destroy($id)
    {
        if (!in_array(Auth::user()->role, ['admin', 'manager'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $pengumuman = Pengumuman::findOrFail($id);
        $pengumuman->delete();

        return response()->json(['message' => 'Pengumuman berhasil dihapus']);
    }
}
