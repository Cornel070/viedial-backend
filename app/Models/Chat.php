<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Encrypt;

class Chat extends Model
{
    use Encrypt;

    protected $encryptable = [
        'user1_name', 'user2_name'
    ];

    public function messages()
    {
    	return $this->hasMany(Message::class);
    }
}
