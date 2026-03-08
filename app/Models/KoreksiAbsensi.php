<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KoreksiAbsensi extends Model
{
    protected $table = 'koreksi_absensi';
    protected $guarded = [];

    public function user() {
        return $this->belongsTo(User::class);
    }
    public function absensi() {
        return $this->belongsTo(Absensi::class);
    }
    public function approver() {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
