<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RemoteMonitoring extends Model
{
    protected $fillable = ['type', 'systolic', 'diastolic', 'blood_sugar_val', 'weight_val', 'waist_line_val', 'timing', 'level'];
}
