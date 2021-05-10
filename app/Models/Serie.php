<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Serie extends Model
{
    public function videos()
    {
    	return $this->hasMany(Video::class);
    }

    public function comments()
    {
    	return $this->hasMany(SerieComment::class);
    }
}
