<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointLedger extends Model
{
    // Matiin updated_at karena mutasi bank nggak boleh diedit
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 
        'transaction_type', 
        'amount', 
        'current_balance', 
        'description'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}