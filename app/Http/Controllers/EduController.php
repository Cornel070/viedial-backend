<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Serie;
use App\Models\Video;
use App\Models\SerieComment;
use App\Models\VideoComment;

class EduController extends Controller
{
	public $user;

	public function __construct()
	{
		$this->user = auth()->user();
	}

    public function index()
    {
    	$category = strtolower($this->user->program);

    	switch ($category) {
    		case 'diabetes':
    			$lecture_series = [];
    			$series = Serie::where('category', 'diabetes')->get();
    			foreach ($series as $serie) {
    				$data = [
    					'id'		=> $serie->id,
    					'title' 	=> $serie->title,
    					'category'	=> 'Type 2 Diabetes',
    					'videos_count'	=> $serie->videos->count(),
    					'likes'		=> $serie->likes,
    					'dislikes'	=> $serie->dislikes,
    					'comments_count'  => $serie->comments->count(),
                        'created_at'      => $serie->created_at,
    				];
    				array_push($lecture_series, $data);
    			}
    			return response()->json(['res_type'=>'success', 'series'=>$lecture_series]);
    			break;
    		case 'hypertension':
    			$lecture_series = [];
    			$series = Serie::where('category', 'hypertension')->get();
    			foreach ($series as $serie) {
    				$data = [
    					'id'		=> $serie->id,
    					'title' 	=> $serie->title,
    					'category'	=> 'Hypertension',
    					'videos_count'	=> $serie->videos->count(),
    					'likes'		=> $serie->likes,
    					'dislikes'	=> $serie->dislikes,
    					'comments_count'  => $serie->comments->count(),
                        'created_at'      => $serie->created_at,
    				];
    				array_push($lecture_series, $data);
    			}
    			return response()->json(['res_type'=>'success', 'series'=>$lecture_series]);
    			break;
    		case 'co-morbidity':
    			$lecture_series = [];
    			$series = Serie::where('category', 'diabetes')->get();
    			foreach ($series as $serie) {
    				$data = [
    					'id'		=> $serie->id,
    					'title' 	=> $serie->title,
    					'category'	=> $serie->category === 'diabetes'?'Type 2 Diabetes':'Hypertension',
    					'videos_count'	=> $serie->videos->count(),
    					'likes'		=> $serie->likes,
    					'dislikes'	=> $serie->dislikes,
    					'comments_count'  => $serie->comments->count(),
                        'created_at'      => $serie->created_at,
    				];
    				array_push($lecture_series, $data);
    			}
    			return response()->json(['res_type'=>'success', 'series'=>$lecture_series]);
    			break;
    		
    		default:
    			return response()->json(['res_type'=>'Not found', 'message'=>'User category not detected.'],404);
    			break;
    	}
    }

    public function seriesVideos($id)
    {
    	$serie = Serie::find($id);

    	if (!$serie) {
    		return response()->json(['res_type'=>'Not found', 'message'=>'Lecture series not found.'],404);
    	}

    	if ($serie->videos->isEmpty()) {
    		return response()->json(['res_type'=>'Not found', 'message'=>'No videos yet for this lecture series.'],404);
    	}

    	$vidData = [];
    	foreach ($serie->videos as $video) {
    		$data = [
    			'id'		 => $video->id,
    			'serie_id'	 => $video->serie_id,
    			'title'		 => $video->title,
    			'video_url'	 => $video->video_url,
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
    	$serie = Serie::find($id);

    	if (!$serie) {
    		return response()->json(['res_type'=>'Not found', 'message'=>'Lecture series not found.'],404);
    	}

    	if ($serie->videos->isEmpty()) {
    		return response()->json(['res_type'=>'Not found', 'message'=>'No comments yet for this lecture series.'],404);
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

    public function commentOnSeries(Request $request, $id)
    {
    	$validator = $this->validateComment($request);

        if ($validator->fails())
        {
            return response()->json(['res_type'=> 'validator_error', 'errors'=>$validator->errors()->all()],422);
        }

        $comment = New SerieComment;
        $comment->serie_id = $id;
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
    	$serie = Serie::find($id);

    	if (!$serie) {
    		return response()->json(['res_type'=>'Not found', 'message'=>'Lecture series not found.'],404);
    	}

    	$serie->likes = $series->likes+1;
    	$serie->save();

    	return response()->json(['res_type'=>'success', 'message'=>'Series liked']);
    }

    public function dislikeSeries($id)
    {
    	$serie = Serie::find($id);

    	if (!$serie) {
    		return response()->json(['res_type'=>'Not found', 'message'=>'Lecture series not found.'],404);
    	}

    	$serie->dislikes = $series->dislikes+1;
    	$serie->save();

    	return response()->json(['res_type'=>'success', 'message'=>'Series disliked']);
    }

    public function commentOnVideo(Request $request, $id)
    {
    	$validator = $this->validateComment($request);

        if ($validator->fails())
        {
            return response()->json(['res_type'=> 'validator_error', 'errors'=>$validator->errors()->all()],422);
        }

        $comment = New VideoComment;
        $comment->video_id = $id;
        $comment->user_id  = $this->user->id;
        $comment->comment_text = $request->comment_text;
        $comment->save();

        return response()->json(['res_type'=>'success', 'message'=>'Commented']);
    }

    public function videoComments($id)
    {
    	$video = Video::find($id);

    	if (!$serie) {
    		return response()->json(['res_type'=>'Not found', 'message'=>'Video not found.'],404);
    	}

    	if ($serie->videos->isEmpty()) {
    		return response()->json(['res_type'=>'Not found', 'message'=>'No comments yet for this video.'],404);
    	}

    	$commData = [];

    	foreach ($video->comments as $comm) {
    		$data = [
    			'id'		 => $comm->id,
    			'video_id'	 => $comm->video_id,
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
    	$video = Video::find($id);

    	if (!$video) {
    		return response()->json(['res_type'=>'Not found', 'message'=>'Video not found.'],404);
    	}

    	$video->likes = $svideo->likes+1;
    	$video->save();

    	return response()->json(['res_type'=>'success', 'message'=>'Video liked']);
    }

    public function dislikeVideo($id)
    {
    	$video = Video::find($id);

    	if (!$video) {
    		return response()->json(['res_type'=>'Not found', 'message'=>'Video not found.'],404);
    	}

    	$video->dislikes = $svideo->dislikes+1;
    	$video->save();

    	return response()->json(['res_type'=>'success', 'message'=>'Video disliked']);
    }
}
