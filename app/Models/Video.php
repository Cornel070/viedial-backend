<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
	protected $fillable = ['serie_id', 'title', 'video_url'];
	
    public function serie()
    {
    	return $this->belongsTo(Serie::class);
    }

    public function comments()
    {
    	return $this->hasMany(VideoComment::class);
    }
}
