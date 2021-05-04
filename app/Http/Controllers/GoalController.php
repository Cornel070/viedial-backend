<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Goal;

class GoalController extends Controller
{
    public function saveGoal(Request $request)
    {
    	$validator = $this->validator($request);

        if ($validator->fails())
        {
            return response()->json(['res_type'=> 'validator_error', 'errors'=>$validator->errors()->all()],404);
        }

        $weekly_deficit  = $request->weekly_deficit??$this->suggestWeeklyDeficit($request);
        $deficit_weight  = $this->setDeficitWeight($request);
        $goal_span		 = $this->setGoalSpan($deficit_weight, $weekly_deficit);

        $data = [
        	'title' 		  	=> $request->title,
        	'set_weight'	    => $request->set_weight,
        	'target_weight'		=> $request->target_weight,
        	'deficit_weight'	=> $deficit_weight,
        	'weekly_deficit'	=> $weekly_deficit,
        	'length'			=> $goal_span,
	        'user_id'			=> $request->user_id
        ];

        $goal = Goal::create($data);

        return response()->json(['res_type'=> 'success', 'goal'=> $goal]);
    }

    public function validator(Request $request)
    {
        return validator()->make($request->all(), [
            'set_weight'     => 'required|integer',
            'target_weight'  => 'required|integer',
            'weekly_deficit' => 'integer',
            'title'			 => 'required|string',
            'user_id'		 => 'required|integer',
        ]);
    }

    public function suggestWeeklyDeficit()
    {
    	return 1.0;
    }

    public function setDeficitWeight(request $request)
    {
    	return $request->set_weight - $request->target_weight;
    }

    public function setGoalSpan($deficit_weight, $weekly_deficit)
    {
    	return ceil($deficit_weight/$weekly_deficit);
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
    		$validator = $this->validator($request);

	        if ($validator->fails())
	        {
	            return response()->json(['res_type'=> 'validator_error', 'errors'=>$validator->errors()->all()],422);
	        }

		    $weekly_deficit = $request->weekly_deficit??$this->suggestDailyDeficit($request);
	        $deficit_weight = $this->setDeficitWeight($request);
	        $goal_span		= $this->setGoalSpan($deficit_weight, $weekly_deficit);

	        $data = [
	        	'title' 		  	=> $request->title,
	        	'set_weight'	    => $request->set_weight,
	        	'target_weight'		=> $request->target_weight,
	        	'deficit_weight'	=> $deficit_weight,
	        	'weekly_deficit'	=> $weekly_deficit,
	        	'length'			=> $goal_span,
	        	'user_id'			=> $request->user_id
	        ];

	        $goal->update($data);

	        return response()->json(['res_type'=> 'success', 'goal'=> $goal]);
    	}
    	return response()->json(['res_type'=> 'error', 'message'=> 'Goal not found'],404);
    }

    public function allGoals($user_id)
    {
    	$goals = Goal::where('user_id', $user_id)->get();
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
    		return response()->json(['res_type'=> 'success']);
    	}
    	return response()->json(['res_type'=> 'error', 'message'=> 'Goal not found'],404);
    }
}
