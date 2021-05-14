<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workout extends Model
{
    public function comments()
    {
    	return $this->hasMany(WorkoutComment::class);
    }
}
