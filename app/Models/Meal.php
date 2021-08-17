<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Meal extends Model
{
    protected $fillable = [
        'title',
        'type',
        'nutri_info',
        'prepare_type',
        'prepare_text',
        'prepare_url',
        'meal_img',
        'tags'
    ];

    public function items()
    {
        return $this->hasMany(MealItem::class);
    }
}
