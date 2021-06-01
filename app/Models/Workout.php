<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workout extends Model
{
    protected $fillable = ['phy_category_id', 'title', 'workout_url'];
    
    public function comments()
    {
    	return $this->hasMany(WorkoutComment::class);
    }
}
