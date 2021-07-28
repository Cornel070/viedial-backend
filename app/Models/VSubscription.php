<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VSubscription extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
