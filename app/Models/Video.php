<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    public function serie()
    {
    	return $this->belongsTo(Serie::class);
    }

    public function comments()
    {
    	return $this->hasMany(VideoComment::class);
    }
}
