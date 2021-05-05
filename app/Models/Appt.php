<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Appt extends Model
{
    protected $fillable = ['appt_date','appt_time','reason','recipient_name', 'recipient_id', 'requestee_name', 'requestee_id', 'status'];

    public  function setApptDateAttribute($val)
    {
    	return $this->attributes['appt_date'] = Carbon::parse($val);
    }

    public  function setApptTimeAttribute($val)
    {
    	return $this->attributes['appt_time'] = Carbon::parse($val);
    }
}
