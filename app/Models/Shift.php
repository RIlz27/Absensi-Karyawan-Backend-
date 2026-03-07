<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = ['nama', 'jam_masuk', 'jam_pulang', 'warna'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_shifts');
    }

    public function hariKerja()
    {
        return $this->hasMany(ShiftHari::class, 'shift_id');
    }
}