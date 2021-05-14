<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PhyCategory;
use App\Models\Workout;
use App\Models\PhyComment;
use App\Models\WorkoutComment;

class PhysicalController extends Controller
{
    public $user;

	public function __construct()
	{
		$this->user = auth()->user();
	}

    public function index()
    {
    	$workout_series = [];
    	$series = PhyCategory::all();
    	if ($series->isEmpty()) {
    		return response()->json(['res_type'=>'Not found', 'message'=>'No workout series yet.'],404);
    	}
    	foreach ($series as $serie) {
    		$data = [
    			'id'			=> $serie->id,
    			'title' 		=> $serie->title,
    			'workout_count'	=> $serie->workouts->count(),
    			'likes'			=> $serie->likes,
    			'dislikes'		=> $serie->dislikes,
    			'comments_count'=> $serie->comments->count(),
                'created_at'    => $serie->created_at,
    		];
    		array_push($workout_series, $data);
    	}
    	return response()->json(['res_type'=>'success', 'series'=>$workout_series]);
    }

    public function seriesWorkouts($id)
    {
    	$serie = PhyCategory::find($id);

    	if (!$serie) {
    		return response()->json(['res_type'=>'Not found', 'message'=>'Workout series not found.'],404);
    	}

    	if ($serie->workouts->isEmpty()) {
    		return response()->json(['res_type'=>'Not found', 'message'=>'No videos yet for this workout series.'],404);
    	}

    	$vidData = [];
    	foreach ($serie->workouts as $video) {
    		$data = [
    			'id'		 => $video->id,
    			'serie_id'	 => $video->serie_id,
    			'title'		 => $video->title,
    			'workout_url'	 => $video->workout_url,
    			'likes'		 => $vide0->likes,
    			'dislikes'	 => $video->dislikes,
    			'comments_count'=> $video->comments->count(),
                'created_at'      => $video->created_at,
    		];
    		array_push($vidData, $data);
    	}

    	return response()->json(['res_type'=>'success', 'videos'=>$vidData]);
    }

    public function seriesComments($id)
    {
    	$serie = PhyCategory::find($id);

    	if (!$serie) {
    		return response()->json(['res_type'=>'Not found', 'message'=>'Workout series not found.'],404);
    	}

    	if ($serie->comments->isEmpty()) {
    		return response()->json(['res_type'=>'Not found', 'message'=>'No comments yet for this workout series.'],404);
    	}

    	$commData = [];

    	foreach ($serie->comments as $comm) {
    		$data = [
    			'id'		 => $comm->id,
    			'serie_id'	 => $comm->serie_id,
    			'by'	 	 => $comm->user->annon_name,
    			'comment_text'	 => $comm->comment_text,
                'created_at'      => $comm->created_at,
    		];
    		array_push($commData, $data);
    	}

    	return response()->json(['res_type'=>'success', 'comments'=>$commData]);
    }

    public function commentOnPhy(Request $request, $id)
    {
    	$validator = $this->validateComment($request);

        if ($validator->fails())
        {
            return response()->json(['res_type'=> 'validator_error', 'errors'=>$validator->errors()->all()],422);
        }

        $comment = New PhyComment;
        $comment->phy_category_id = $id;
        $comment->user_id  = $this->user->id;
        $comment->comment_text = $request->comment_text;
        $comment->save();

        return response()->json(['res_type'=>'success', 'message'=>'Commented']);
    }

    public function validateComment(Request $request)
    {
        $msg = [
            'comment_text.required' => 'Please enter a comment',
        ];
        return validator()->make($request->all(), [
            'comment_text' => 'required',
        ], $msg);
    }

    public function likeSeries($id)
    {
    	$serie = PhyCategory::find($id);

    	if (!$serie) {
    		return response()->json(['res_type'=>'Not found', 'message'=>'Workout series not found.'],404);
    	}

    	$serie->likes = $series->likes+1;
    	$serie->save();

    	return response()->json(['res_type'=>'success', 'message'=>'Series liked']);
    }

    public function dislikeSeries($id)
    {
    	$serie = PhyCategory::find($id);

    	if (!$serie) {
    		return response()->json(['res_type'=>'Not found', 'message'=>'Workout series not found.'],404);
    	}

    	$serie->dislikes = $series->dislikes+1;
    	$serie->save();

    	return response()->json(['res_type'=>'success', 'message'=>'Series disliked']);
    }

    public function commentOnWorkout(Request $request, $id)
    {
    	$validator = $this->validateComment($request);

        if ($validator->fails())
        {
            return response()->json(['res_type'=> 'validator_error', 'errors'=>$validator->errors()->all()],422);
        }

        $comment = New WorkoutComment;
        $comment->workout_id = $id;
        $comment->user_id  = $this->user->id;
        $comment->comment_text = $request->comment_text;
        $comment->save();

        return response()->json(['res_type'=>'success', 'message'=>'Commented']);
    }

    public function workoutComments($id)
    {
    	$workout = Workout::find($id);

    	if (!$workout) {
    		return response()->json(['res_type'=>'Not found', 'message'=>'workout not found.'],404);
    	}

    	if ($workout->comments->isEmpty()) {
    		return response()->json(['res_type'=>'Not found', 'message'=>'No comments yet for this workout.'],404);
    	}

    	$commData = [];

    	foreach ($workout->comments as $comm) {
    		$data = [
    			'id'		 => $comm->id,
    			'workout_id' => $comm->workout_id,
    			'by'	 	 => $comm->user->annon_name,
    			'comment_text'	 => $comm->comment_text,
                'created_at'      => $comm->created_at,
    		];
    		array_push($commData, $data);
    	}

    	return response()->json(['res_type'=>'success', 'comments'=>$commData]);
    }

    public function likeVideo($id)
    {
    	$video = Workout::find($id);

    	if (!$video) {
    		return response()->json(['res_type'=>'Not found', 'message'=>'Workout not found.'],404);
    	}

    	$video->likes = $svideo->likes+1;
    	$video->save();

    	return response()->json(['res_type'=>'success', 'message'=>'Workout liked']);
    }

    public function dislikeVideo($id)
    {
    	$video = Workout::find($id);

    	if (!$video) {
    		return response()->json(['res_type'=>'Not found', 'message'=>'Workout not found.'],404);
    	}

    	$video->dislikes = $svideo->dislikes+1;
    	$video->save();

    	return response()->json(['res_type'=>'success', 'message'=>'Workout disliked']);
    }
}
