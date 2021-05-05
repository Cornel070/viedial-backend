<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Appt;
use App\Models\User;
use App\Events\Appointment;
use App\Events\ApptAccepted;
use App\Events\ApptDeclined;

class ApptController extends Controller
{
    protected $user;

    public function __construct()
    {
        $this->user = auth()->user();
    }

    public function setAppt(Request $request)
    {
    	$validator = $this->validateAppt($request);

        if ($validator->fails())
        {
            return response()->json(['res_type'=> 'validator_error', 'errors'=>$validator->errors()->all()],422);
        }

        $to_user = User::find($request->to);

        if (!$to_user) {
            return response()->json(['res_type'=>'Not found', 'message'=>'Recipient user does not exist'],404);
        }

        $data = [
            'appt_date'      => $request->appt_date,
            'appt_time'      => $request->appt_time,
            'reason'         => $request->reason,
            'recipient_name' => $to_user->name,
            'recipient_id'   => $to_user->id,
            'requestee_name' => $this->user->name,
            'requestee_id'   => $this->user->id,
            'status'         => 'Pending'
        ];

        $appt = Appt::create($data);

        broadcast(new Appointment($appt))->toOthers();

        return response()->json(['res_type'=>'success', 'message'=>'Appointment request sent', 'appt' => $appt],200);
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
            'to'          => 'required|integer',
        ], $msg);
    }

    public function acceptApptRequest($id)
    {
        $appt = Appt::find($id);

        if (!$appt) {
            return response()->json(['res_type'=>'Not found', 'message'=>'Appointment does not exist'],404);
        }

        $appt->status = 'Accepted';
        $appt->save();

        broadcast(new ApptAccepted($appt))->toOthers();

        return response()->json(['res_type'=>'success', 'message'=>'Appointment request accepted', 'appt'=>$appt],200);
    }

    public function declineApptRequest(Request $request, $id)
    {
        $validator = $this->validateDecline($request);

        if ($validator->fails())
        {
            return response()->json(['res_type'=> 'validator_error', 'errors'=>$validator->errors()->all()],422);
        }

        $appt = Appt::find($id);

        if (!$appt) {
            return response()->json(['res_type'=>'Not found', 'message'=>'Appointment does not exist'],404);
        }

        $appt->status = 'Declined';
        $appt->save();

        broadcast(new ApptDeclined($appt, $request->reason))->toOthers();

        return response()->json(['res_type'=>'success', 'message'=>'Appointment request declined', 'appt'=>$appt],200);
    }

    public function validateDecline($request)
    {
        $msg = [
            'reason.required'    => 'Please tell us why the appointment is been declined',
        ];

        return validator()->make($request->all(), [
            'reason'   => 'required'
        ], $msg);
    }
}
