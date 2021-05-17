<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Goal;

class GoalController extends Controller
{
    protected $user;

    public function __construct()
    {
        $this->user = auth()->user();
    }

    public function saveGoal(Request $request)
    {
    	$validator = $this->validateGoal($request);

        if ($validator->fails())
        {
            return response()->json(['res_type'=> 'validator_error', 'errors'=>$validator->errors()->all()],422);
        }

        list($weekly_deficit, $calorie_deficit)  = $request->weekly_deficit ? 
        $this->getCalorieEq($request->weekly_deficit) : $this->suggestWeeklyDeficit($request);
        $calorie_balance = $this->getCalorieTarget($calorie_deficit);
        $deficit_weight  = $this->setDeficitWeight($request);
        $goal_span		 = $this->setGoalSpan($deficit_weight, $weekly_deficit, true);

        $data = [
        	'title' 		  	=> $request->title,
        	'current_weight'	=> $request->current_weight,
        	'target_weight'		=> $request->target_weight,
            'calorie_balance'   => $calorie_balance,//####
        	'deficit_weight'	=> $deficit_weight,
        	'weekly_deficit'	=> $weekly_deficit,
            'weekly_calorie_def'=> $calorie_deficit,//###
        	'length'			=> $goal_span,
	        'user_id'			=> $this->user->id
        ];

        $goal = Goal::create($data);

        return response()->json(['res_type'=> 'success', 'goal'=> $goal]);
    }

    public function validateGoal(Request $request)
    {
        $msg = [
            'current_weight.required'=>'Your current weight is required',
            'current_weight.integer' => 'Your current weight must be a valid number',
            'target_weight.required' => 'The target weight is required',
            'target_weight.required' => 'The target weight must be a valid number',
            'weekly_deficit.integer'=> 'Please set a weekly weight deficit',
            'title.required'        => 'A title is required for the goal'
        ];
        return validator()->make($request->all(), [
            'current_weight' => 'required|integer',
            'target_weight'  => 'required|integer',
            'weekly_deficit' => 'required',
            'title'			 => 'required|string',
        ],$msg);
    }

    public function suggestWeeklyDeficit()
    {
    	return array(0.90, 6930);
    }

    public function getCalorieEq($val)
    {
        switch ($val) {
            case 0.10:
                return array(0.10, 770);
                break;
            case 0.20:
                return array(0.20, 1540);
                break;
            case 0.30:
                return array(0.30, 2310);
                break;
            case 0.40:
                return array(0.40, 3080);
                break;
            case 0.50:
                return array(0.50, 3850);
                break;
            case 0.60:
                return array(0.60, 4620);
                break;
            case 0.70:
                return array(0.70, 5390);
                break;
            case 0.80:
                return array(0.80, 6160);
                break;
            case 0.90:
                return array(0.90, 6930);
                break;
            case 1.0:
                return array(1.0, 7700);
                break;

            default:
                return array(0.90, 6930);
                break;
        }
    }

    public function getCalorieTarget($val)
    {
        if ($user->gender === 'Male' || $user->gender === 'male') {
            $stayAliveCalorie = 2500 * 7; //calories needed  to stay alive for the week (Male);
            return $stayAliveCalorie - $val; 
        }elseif ($user->gender === 'Female' || $user->gender === 'female') {
            $stayAliveCalorie = 2000 * 7; //calories needed  to stay alive for the week (Female);
            return $stayAliveCalorie - $val; 
        }
    }

    public function setDeficitWeight(Request $request)
    {
    	return (int) $request->current_weight - (int) $request->target_weight;
    }

    public function setGoalSpan($deficit_weight, $weekly_deficit, $within = false)
    {
        $weeks = ceil($deficit_weight/$weekly_deficit);

    	if ($within) {
            return $weeks;
        }

        return response()->json(['res_type'=> 'success', 'weeks'=> $weeks, 'days'=> $weeks*7]);
    }

    public function showGoal($id)
    {
    	$goal = Goal::find($id);

    	if ($goal) {
    		return response()->json(['res_type'=> 'success', 'goal'=> $goal]);
    	}
    	return response()->json(['res_type'=> 'error', 'message'=> 'Goal not found'],404);
    }

    public function updateGoal(Request $request, $id)
    {
    	$goal = Goal::find($id);

    	if ($goal) {
            list($weekly_deficit, $calorie_deficit)  = $request->weekly_deficit ? 
            $this->getCalorieEq($request->weekly_deficit) : $this->suggestWeeklyDeficit($request);
            $calorie_balance = $this->getCalorieTarget($calorie_deficit);
            $deficit_weight  = $this->setDeficitWeight($request);
            $goal_span       = $this->setGoalSpan($deficit_weight, $weekly_deficit, true);
	        return response()->json(['res_type'=> 'success', 'goal'=> $goal]);
    	}
    	return response()->json(['res_type'=> 'error', 'message'=> 'Goal not found'],404);
    }

    public function allGoals()
    {
    	$goals = Goal::where('user_id', $this->user->id)->get();
    	if ($goals->isEmpty()) {
    		return response()->json(['res_type'=> 'error', 'message'=> 'No goals found'],204);
    	}

    	return response()->json(['res_type'=> 'success', 'goals' => $goals]);
    }

    public function deleteGoal($id)
    {
    	$goal = Goal::find($id);
    	if ($goal) {
    		$goal->delete();
    		return response()->json(['res_type'=> 'success', 'message'=>'Goal deleted']);
    	}
    	return response()->json(['res_type'=> 'error', 'message'=> 'Goal not found'],404);
    }
}
