<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QrCode extends Model
{

    protected $table = 'qr_codes'; // ← PENTING
    protected $fillable = [
        'kode',
        'kantor_id',
        'expired_at',
        'is_active'
    ];

    public function kantor()
    {
        return $this->belongsTo(Kantor::class);
    }
}
