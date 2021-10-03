<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\MealSummary;
use App\Models\WorkoutTracker;
use App\Models\Goal;
use App\Models\Telemonitoring;
use App\Models\Workout;
use App\Models\RemoteMonitoring;

class UpdateController extends Controller
{
	public $user;

	public function __construct()
	{
		$this->user = auth()->user();
	}

    public function updatePhone(Request $request)
    {
    	$validator = $this->validatePhone($request);

        if ($validator->fails())
        {
            return response()->json(['success'=>false,'errors'=>$validator->errors()->all()], 422);
        }

        $this->user->phone = $request->phone;
        $this->user->save();

        return response()->json(['res_type'=>'success', 'message'=>'Phone number updated']);
    }

    public function validatePhone(Request $request)
    {
    	$msg = ['phone.required'=>'Please enter a phone number'];
    	return validator()->make($request->all(), [
            'phone' => 'required'
        ], $msg);
    }

    public function dashboard()
    {
        $dash_data = [];

        $incomplete = [];

        /*
            Telemonitoring
        */

        //record incomplete action
        $count = 0;

        // Blood Pressure Today 
        $todays_bp = RemoteMonitoring::whereDate('created_at', Carbon::today())
                                      ->where('user_id', $this->user->id)
                                      ->where('type', 'blood_pressure')
                                      ->latest()
                                      ->first();
        if (!$todays_bp) {
            $count++;
        }

        // Blood Sugar Today
        $todays_bs = RemoteMonitoring::whereDate('created_at', Carbon::today())
                                      ->where('user_id', $this->user->id)
                                      ->where('type', 'blood_sugar')
                                      ->latest()
                                      ->first();
        if (!$todays_bs) {
            $count++;
        }

        // Weight Today
        $todays_weight = RemoteMonitoring::whereDate('created_at', Carbon::today())
                                      ->where('user_id', $this->user->id)
                                      ->where('type', 'weight')
                                      ->latest()
                                      ->first();
        if (!$todays_weight) {
            $count++;
        }

        if ($count == 3) {
            $tele_data = [
                'blood_pressure_systolic'   => 0,
                'blood_pressure_diastolic'  => 0,
                'blood_sugar'               => 0,
                'weight'                    => 0
            ];

            //record incomplete action
            $data = [
                'type' => 'telemonitoring',
                'count' => 3,
                'message'=>'Reading(s) not entered today'
            ];

            array_push($incomplete, $data);
        }elseif ($count > 0) {
            $tele_data = [
                'blood_pressure_systolic'  => $todays_bp->systolic,
                'blood_pressure_diastolic' => $todays_bp->diastolic,
                'blood_sugar'              => $todays_bs->blood_sugar_val,
                'weight'                   => $todays_weight->weight_val
            ];

            $data = [
                'type' => 'telemonitoring',
                'count' => $count,
                'message'=> 'Reading(s) not entered'
            ];
            array_push($incomplete, $data);
        }elseif ($count == 0) {
            $tele_data = [
                'blood_pressure_systolic'  => $todays_bp->systolic,
                'blood_pressure_diastolic' => $todays_bp->diastolic,
                'blood_sugar'              => $todays_bs->blood_sugar_val,
                'weight'                   => $todays_weight->weight_val
            ];
        }

        $dash_data['daily_readings'] = $tele_data;

        /*
            Telemonitoring ends
        */


        /*
            Meal Statistics
        */

        $todays_meals = MealSummary::whereDate('created_at', Carbon::today())
                                        ->where('user_id', $this->user->id)
                                        ->select('breakfast', 'lunch', 'dinner')
                                       ->first();
        $suggested_meal = 3;
        $eaten = 0;

        //incomplete actions count
        $count = 0;
        if ($todays_meals) {
            if ($todays_meals->breakfast == 'yes') {
                $eaten = $eaten + 1; 
            }else{
                $count = $count + 1;
            }
            if ($todays_meals->lunch == 'yes') {
                $eaten = $eaten + 1; 
            }else{
                $count = $count + 1;
            }
            if ($todays_meals->dinner == 'yes') {
                $eaten = $eaten + 1; 
            }else{
                $count = $count + 1;
            }
        }else{
            $count = 3;
        }

        switch ($eaten) {
            case 1:
                $percentage = 33;
                break;
            case 2:
                $percentage = 66;
                break;
            case 3:
                $percentage = 100;
                break;
            
            default:
                $percentage = 0;
                break;
        }

        //if some meals not eaten
        if ($count > 0) {
            $data = [
                'type' => 'meals',
                'count' => $count,
                'message'=> 'Meal(s) not eaten'
            ];
            array_push($incomplete, $data);
        }

        $meal_data = [
            'suggested' => $suggested_meal,
            'eaten'     => $eaten,
            'percentage'=> $percentage,
        ];

        $dash_data['meal_statistics'] = $meal_data;
        /*
            Meal Statistics Ends
        */


        /*
            Physical Activity
        */


       $suggested_phy = Workout::count();
       $done = $this->user->workouts()->count();

       if ($done != 0) {
           $percentage = ( ceil($suggested_phy / $done) ) * 100 ;
       }else{
            $percentage = 0;

            //record incomplete action
            $data = [
                'type' => 'physical',
                'count' => 1,
                'message'=>'Exercise(s) not done today'
            ];

            array_push($incomplete, $data);
       }

       $phy_data = [
        'suggested' => $suggested_phy,
        'done'      => $done,
        'percentage'=> $percentage
       ];

       $dash_data['phy_activity'] = $phy_data;
       /*
            Physical Activity Ends
        */


        /*
            Goals
        */
        $to_burn = Goal::where('user_id', $this->user->id)
                        ->where('status', 'in progress')
                        ->select('weekly_calorie_def')
                        ->first();
        $burned = 0;
        if ($to_burn) {
            foreach ($this->user->workouts() as $done) {
                $burned = $burned + (int) $done->workout->calorie_burn;
            }

            if ($burned != 0) {
                $percentage = ( ceil($to_burn->weekly_calorie_def / $burned) ) * 100;
            }else{
                $percentage = 0;
            }

            $goal_data = [
                'calorie_to_burn' => $to_burn->weekly_calorie_def,
                'calorie_burned'  => $burned,
                'percentage'      => $percentage
            ];
        }else{
            $goal_data = null;
        }

        $dash_data['goal'] = $goal_data;
        /*
            Goals Ends
        */


        /*
            Incomplete actions
        */

        $dash_data['incomplete'] = $incomplete;
        /*
            Incomplete actions end
        */


        /*
            Leaderboard
        */
        $goal_users = Goal::where('status', 'in progress')->orderBy('calorie_burned_this_week', 'desc')->limit(3)->get();
        $board = [];

        foreach ($goal_users as $goal) {
            // $burned = 0;
            // foreach ($goal->user->workouts() as $done) {
            //     $burned = $burned + (int) $done->workout->calorie_burn;
            // }
            $data = [
                'name'  => $goal->user->annon_name,
                'burned'=> (int) $goal->calorie_burned_this_week
            ];

            array_push($board, $data);
        }

        $dash_data['leaderboard'] = $board;

        return response()->json(['res_type'=>'success', 'dash_data'=>$dash_data]);
    }
}
