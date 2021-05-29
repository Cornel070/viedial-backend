<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodItem extends Model
{
    protected $fillable = [
                'food_type_id',
                'name',
                'calorie_count',
                'carb_count'
            ];

    public function foodType()
    {
        return $this->belongsTo(FoodType::class);
    }
}
