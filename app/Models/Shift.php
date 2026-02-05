<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $table = 'shifts';

    protected $fillable = [
        'nama',
        'jam_masuk',
        'jam_pulang',
        'toleransi_menit'
    ];
}
