<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Goal extends Model
{
    protected $fillable = [
    	'title', 'set_weight', 'user_id',
    	'target_weight', 'deficit_weight',
    	'length', 'weekly_deficit', 'status'
    ];

    public function getLengthAttribute($val)
    {
        if ($val > 1) {
            return $val.' weeks';
        }
        
    	return $val.' week';
    }

    public  function setCurrentWeightAttribute($val)
    {
    	return $this->attributes['set_weight'] = (int) $val;
    }

     public  function setTargetWeightAttribute($val)
    {
    	return $this->attributes['target_weight'] = (int) $val;
    }
}
