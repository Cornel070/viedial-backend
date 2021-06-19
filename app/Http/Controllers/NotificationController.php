<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Appt;
use Illuminate\Http\Request;
use App\Models\Telemonitoring;
use App\Models\WorkoutTracker;
use App\Models\MealTracker;
use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use FCM;

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
                $data = collect(["title"=>"Telemonitoring", "description"=>"You have not entered your blood pressure reading for today"]);
                array_push($summary, $data);
            }
            if (!$tele->blood_sugar) {
                $data = collect(["title"=>"Telemonitoring", "description"=>"You have not entered your blood sugar reading for today"]);
                array_push($summary, $data);
            }
            if (!$tele->weight) {
                $data = collect(["title"=>"Telemonitoring", "description"=>"You have not entered your weight reading for today"]);
                array_push($summary, $data);
            }

            // if (count($teleData) > 0) {
            //     $summary['Telemonitoring'] = $teleData;
            // }
        }else{
            // array_push($teleData, "You have not entered any of your readings today");
            // $summary['Telemonitoring'] = $teleData;
            $data = collect(["title"=>"Telemonitoring", "description"=>"You have not entered any of your readings today"]);
            array_push($summary, $data);
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
        ->whereDate('appt_date', Carbon::today())
    	->get();

    	if (!$appts->isEmpty()) {
    		$apptsArr = [];
    		foreach ($appts as $appt) {
    			$recipient_name = $appt->requestee_id == $this->user->id ? $appt->requestee_name : $appt->recipient_name;
    			// array_push($apptsArr, "You have an appointment today with ".$recipient_name);
                $data = collect(["title"=>"Appointments", "description"=>"You have an appointment today with ".$recipient_name]);
                array_push($summary, $data);
    		}

    		// $summary['Appointments'] = $apptsArr;
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
            $data = collect(["title"=>"Physical Activity", "description"=>"It seems you have not done any exercise today"]);
            array_push($summary, $data);
        }
        /*
            Workout/physical activity ends
        */


        /*
            New user
        */
        if (Carbon::now()->diffInMinutes($this->user->created_at) < 2) {
            $data = collect(["title"=>"New User", "description"=>"Hi ".$this->user->name."! Welcome to the Viedial Family."]);
            array_push($summary, $data);
        }
        /*
            New user ends
        */


        /*
            Meal Suggestions start
        */
          $hour = date("G"); 
          $minute = date("i"); 
          $second = date("s"); 

          if ( (int)$hour == 0 && (int)$hour <= 9 ) { 
            $breakfast = $this->user->breakfast();

            if (!$breakfast) {
                $data = collect(["title"=>"Meal Plan", "description"=>"It seems you have not eaten the suggested breakfast this morning"]);
                array_push($summary, $data);
            }
          } else if ( (int)$hour >= 12 && (int)$hour <= 15 ) { 
            $lunch = $this->user->lunch();
            if (!$lunch) {
                $data = collect(["title"=>"Meal Plan", "description"=>"It seems you have not eaten the suggested lunch this afternoon"]);
                array_push($summary, $data);
            }
          } else if ( (int)$hour >= 16 && (int)$hour <= 23 ) { 
           $dinner = $this->user->dinner();
            if (!$dinner) {
                $data = collect(["title"=>"Meal Plan", "description"=>"It seems you have not eaten the suggested dinner this evening"]);
                array_push($summary, $data);
            }
          }
        /*
            Meal suggestions ends
        */


        return response()->json(['res_type'=>'success', 'summaries'=>$summary]);
    }

    public static function push($device_id, $roomName, $caller)
    {
        try {
            $optionBuilder = new OptionsBuilder();
            $optionBuilder->setTimeToLive(50);

            $notificationBuilder = new PayloadNotificationBuilder($caller);
            $notificationBuilder->setBody('Viedial video call')
                            ->setSound('default');

            $dataBuilder = new PayloadDataBuilder();
            $dataBuilder->addData(['roomName' => $roomName]);

            $option = $optionBuilder->build();
            $notification = $notificationBuilder->build();
            $data = $dataBuilder->build();

            $downstreamResponse = FCM::sendTo($device_id, $option, $notification, $data);

            $downstreamResponse->numberSuccess();
            $downstreamResponse->numberFailure();
            $downstreamResponse->numberModification();

            // return Array - you must remove all this tokens in your database
            $downstreamResponse->tokensToDelete();

            // return Array (key : oldToken, value : new token - you must change the token in your database)
            $downstreamResponse->tokensToModify();

            // return Array - you should try to resend the message to the tokens in the array
            $downstreamResponse->tokensToRetry();

            // return Array (key:token, value:error) - in production you should remove from your database the tokens
            $downstreamResponse->tokensWithError();

            return true;
        } catch (\LaravelFCM\Response\Exceptions\InvalidRequestException $e) {
            return false;
        }
    }
}
