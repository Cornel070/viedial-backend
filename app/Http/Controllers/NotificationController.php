<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Appt;
use Illuminate\Http\Request;
use App\Models\Telemonitoring;
use App\Models\WorkoutTracker;

class NotificationController extends Controller
{
	public $user;

	public function __construct()
	{
		$this->user = auth()->user();
	}

    public function allNotifications()
    {
    	$summary = [];

    	/*
            Telemonitoring starts
        */
    	$tele = Telemonitoring::whereDate('created_at', Carbon::today())->where('user_id', $this->user->id)->first();

        $teleData = [];

    	if ($tele) {
            if (!$tele->blood_pressure_systolic) {
                array_push($teleData, "Enter your blood pressure reading for today");
            }
            if (!$tele->blood_sugar) {
                array_push($teleData, "Enter your blood sugar reading for today");
            }
            if (!$tele->weight) {
                array_push($teleData, "Enter your weight reading for today");
            }

            if (count($teleData) > 0) {
                $summary['Telemonitoring'] = $teleData;
            }
        }else{
            array_push($teleData, "You have not entered any of your readings today");
            $summary['Telemonitoring'] = $teleData;
        }
        /*
            Telemonitoring ends
        */


    	/*
            Appointment starts
        */
    	$appts = Appt::whereDate('appt_date', Carbon::today())
    	->where('requestee_id', $this->user->id)
    	->orWhere('recipient_id', $this->user->id)
    	->get();

    	if (!$appts->isEmpty()) {
    		$apptsArr = [];
    		foreach ($appts as $appt) {
    			$recipient_name = $appt->requestee_id == $this->user->id ? $appt->requestee_name : $appt->recipient_name;
    			array_push($apptsArr, "You have an appointment today with ".$recipient_name);
    		}

    		$summary['Appointments'] = $apptsArr;
    	}

        /*
            Appointment ends
        */


        /*
            Workout/physical activity starts
        */
    	$tracker = WorkoutTracker::whereDate('created_at', Carbon::today())
        ->where('user_id', $this->user->id)
        ->get();

        if ($tracker->count() < 1) {
        	$workoutArr = ["It seems you have not done any exercise today"];
        	$summary['Physical Activity'] = $workoutArr;
        }

        return response()->json(['res_type'=>'success', 'summaries'=>$summary]);
        /*
            Workout/physical activity ends
        */
    }
}
