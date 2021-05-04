<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model {
	protected $fillable = [
		'id', 'question', 'question_type',
		'next_id', 'prev_id'
	];

	public function options()
	{
		return $this->hasMany(Option::class);
	}
}