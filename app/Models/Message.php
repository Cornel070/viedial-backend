<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Encrypt;

class Message extends Model
{
    use Encrypt;

    protected $encryptable = [
        'from_name', 'to_name',
        'message_text',
    ];
}
