<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Izin extends Model
{
    protected $table = 'izins';

    protected $fillable = [
        'user_id',
        'tanggal',
        'jam_mulai',
        'jam_selesai',
        'alasan',
        'status',
        'approved_by',
        'approved_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
