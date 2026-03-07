<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cuti extends Model
{
    protected $table = 'cutis';

    protected $fillable = [
        'user_id',
        'jenis',
        'tanggal_mulai',
        'tanggal_selesai',
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
