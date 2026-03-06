<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    // Balikin fillable-nya biar bisa input data
    protected $fillable = ['nama_shift', 'jam_masuk', 'jam_pulang'];

    // Relasi ke User (Many to Many)
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_shifts');
    }

    public function hariKerja()
    {
        return $this->hasMany(ShiftHari::class, 'shift_id');
    }
}
