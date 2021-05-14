<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhyCategory extends Model
{
    public function workouts()
    {
    	return $this->hasMany(Workout::class);
    }

    public function comments()
    {
    	return $this->hasMany(PhyComment::class);
    }
}
