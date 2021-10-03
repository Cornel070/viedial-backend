<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MealItem extends Model
{
    protected $fillable = ['meal_id', 'food_item_id'];

    public function food()
    {
        return $this->hasOne(FoodItem::class);
    }
}
