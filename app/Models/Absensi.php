<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Absensi extends Model
{
    protected $table = 'absensi';

    protected $fillable = [
        'user_id',
        'shift_id',
        'kantor_id',
        'tanggal',
        'jam_masuk',
        'jam_pulang',
        'latitude',
        'longitude',
        'status',
        'metode'
    ];

    // App/Models/Absensi.php
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function kantor()
    {
        return $this->belongsTo(Kantor::class);
    }

    protected $casts = [
        'jam_masuk' => 'datetime',
        'jam_pulang' => 'datetime',
    ];
}
