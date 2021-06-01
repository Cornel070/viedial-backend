<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VidCommentReply extends Model
{
    protected $table = 'video_comment_replies';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
