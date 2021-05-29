<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodType extends Model
{
    public function items()
    {
        return $this->hasMany(FoodItem::class);
    }
}
