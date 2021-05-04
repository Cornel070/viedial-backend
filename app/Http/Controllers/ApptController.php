<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Appt;
use App\Events\Appointment;

class ApptController extends Controller
{
    public function setAppt(Request $request)
    {
    	$validator = $this->validateAppt($request);

        if ($validator->fails())
        {
            return response()->json(['res_type'=> 'validator_error', 'errors'=>$validator->errors()->all()],422);
        }

        $appt = Appt::create($request->all());

        broadcast(new Appointment($appt))->toOthers();

        return response()->json(['res_type'=>'success', 'message'=>'Appointment request sent'],200);
    }

    public function validateAppt($request)
    {
    	$msg = [
    		'appt_date.required'	=> 'A date is required',
    		'appt_time.required'	=> 'A time is required',
    		'reason.required'		=> 'The reason field is required',
    		'accepted_by.required'  => 'The appointment must have a recipient',
    	];

    	return validator()->make($request->all(), [
            'appt_date'   => 'required',
            'appt_time'   => 'required',
            'reason'	  => 'required|string',
            'accepted_by' => 'required|integer',
        ], $msg);
    }
}
