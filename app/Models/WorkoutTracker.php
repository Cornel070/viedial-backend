<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkoutTracker extends Model
{
    protected $fillable = ['user_id', 'workout_id'];

    public function workout()
    {
        return $this->belongsTo(Workout::class);
    }
}
