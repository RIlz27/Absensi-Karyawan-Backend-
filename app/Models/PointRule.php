<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointRule extends Model
{
    protected $fillable = [
        'rule_name', 
        'target_role', 
        'condition_operator', 
        'condition_value', 
        'point_modifier'
    ];
}