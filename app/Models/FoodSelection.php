<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodSelection extends Model
{
    protected $fillable = ['user_id', 'food_item_id'];
}
