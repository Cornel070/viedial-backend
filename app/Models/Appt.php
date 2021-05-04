<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appt extends Model
{
    protected $fillable = ['appt_date','appt_time','reason','requested_by','accepted_by'];
}
