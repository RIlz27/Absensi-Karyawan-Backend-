<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlexibilityItem extends Model
{
    protected $fillable = [
        'item_name', 
        'point_cost', 
        'stock_limit'
    ];

    public function tokens()
    {
        return $this->hasMany(UserToken::class, 'item_id');
    }
}