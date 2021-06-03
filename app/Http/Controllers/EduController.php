<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Serie;
use App\Models\Video;
use App\Models\SerieComment;
use App\Models\VideoComment;
use App\Models\VidCommentReply;

class EduController extends Controller
{
	public $user;

	public function __construct()
	{
		$this->user = auth()->user();
	}

    public function createSeries(Request $request)
    {
        $validator = $this->validateSeries($request);

        if ($validator->fails())
        {
            return response()->json(['res_type'=> 'validator_error', 'errors'=>$validator->errors()->all()], 422);
        }

        $series = new Serie;
        $series->title = $request->title;
        $series->category = $request->category;
        $series->save();

        if ($request->has('videos')) {
            $this->saveVidoes($request, $series->id, true);
        }

        return response()->json(['res_type'=>'success', 'message'=>'Series created']);
    }

    public function validateSeries(Request $request)
    {
        $msg = [
            'title.required' => 'Title is required',
            'title.string'   => 'Title must be a string',
            'category.required' => 'Category is required',
            'category.string' => 'Category must be a string',
        ];

        return validator()->make($request->all(), [
            'title' => 'required|string',
            'category' => 'required|string',
        ],$msg);
    }

    public function saveVidoes(Request $request, $series_id, $w = false)
    {
        $vidArr = [];
        foreach ($request['videos'] as $key => $value) {
            $vidName = time().'.'.$request->videos[$key]->extension();  
            $request->videos[$key]->move('assets/vids/edu', $vidName);
            array_push($vidArr, [
                'serie_id' => $series_id,
                'title'    => $request['titles'][$key],
                'video_url'=> env('PUBLIC_DIR').'assets/vids/edu/'.$vidName,
            ]);
        }

        for ($i = 0; $i < count($vidArr); $i++) { 
            Video::create($vidArr[$i]);
        }

        // call was from within the class - another function
        if ($w) {
            return true;
        }

        return response()->json(['res_type'=>'success', 'message'=>'Video added']);
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
                        'intro_vid' => $serie->intro_vid,
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
                        'intro_vid' => $serie->intro_vid,
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
                        'intro_vid' => $serie->intro_vid,
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
    			'likes'		 => $video->likes,
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
    		return response()->json(['res_type'=>'Not found', 'message'=>'Lecture series not found.']);
    	}

    	if ($serie->comments->isEmpty()) {
    		return response()->json(['res_type'=>'Not found', 'message'=>'No comments yet for this lecture series.']);
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
            'comment_text.required' => 'Please enter comment text',
            'comment_text.string' => 'Please enter a valid string for the comment',
        ];
        return validator()->make($request->all(), [
            'comment_text' => 'required|string',
        ], $msg);
    }

    public function likeSeries($id)
    {
    	$serie = Serie::find($id);

    	if (!$serie) {
    		return response()->json(['res_type'=>'Not found', 'message'=>'Lecture series not found.'],404);
    	}

    	$serie->likes = $serie->likes+1;
    	$serie->save();

    	return response()->json(['res_type'=>'success', 'message'=>'Series liked']);
    }

    public function dislikeSeries($id)
    {
    	$serie = Serie::find($id);

    	if (!$serie) {
    		return response()->json(['res_type'=>'Not found', 'message'=>'Lecture series not found.'],404);
    	}

    	$serie->dislikes = $serie->dislikes+1;
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

    	if (!$video) {
    		return response()->json(['res_type'=>'Not found', 'message'=>'Video not found.']);
    	}

    	if ($video->comments->isEmpty()) {
    		return response()->json(['res_type'=>'Not found', 'message'=>'No comments yet for this video.']);
    	}

    	$commData = [];

    	foreach ($video->comments as $comm) {
            if ($comm->replies->count() > 0) {
                //get replies
                $replyData = [];

                foreach ($comm->replies as $reply) {
                    $replies = [
                        'id'            => $reply->id,
                        'by'            => $reply->user->annon_name,
                        'reply_text'    => $reply->reply_text,
                        'likes'         => $reply->likes,
                        'dislikes'      => $reply->dislikes,
                        'created_at'    => $reply->created_at,
                    ];
                    array_push($replyData, $replies);
                }
            }else{
                $replyData = null;
            }

            $data = [
                'id'            => $comm->id,
                'by'            => $comm->user->annon_name,
                'comment_text'  => $comm->comment_text,
                'likes'         => $comm->likes,
                'dislikes'      => $comm->dislikes,
                'created_at'    => $comm->created_at,
                'replies'       => $replyData
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

    	$video->likes = $video->likes+1;
    	$video->save();

    	return response()->json(['res_type'=>'success', 'message'=>'Video liked']);
    }

    public function dislikeVideo($id)
    {
    	$video = Video::find($id);

    	if (!$video) {
    		return response()->json(['res_type'=>'Not found', 'message'=>'Video not found.']);
    	}

    	$video->dislikes = $video->dislikes+1;
    	$video->save();

    	return response()->json(['res_type'=>'success', 'message'=>'Video disliked']);
    }

    public function replyVideoComments(Request $request, $id)
    {
        $validator = $this->validateReply($request);

        if ($validator->fails())
        {
            return response()->json(['res_type'=> 'validator_error', 'errors'=>$validator->errors()->all()],422);
        }

        $reply = new VidCommentReply;
        $reply->video_comment_id = $id;
        $reply->user_id = $this->user->id;
        $reply->reply_text = $request->reply_text;
        $reply->save();

        return response()->json(['res_type'=>'success', 'message'=>'Replied']);
    }

    public function validateReply(Request $request)
    {
        $msg = [
            'reply_text.required' => 'Please enter a reply',
            'reply_text.string'      => 'The reply must be a valid text'
        ];
        return validator()->make($request->all(), [
            'reply_text' => 'required|string',
        ], $msg);
    }

    public function singleComment($id)
    {
        $comment = VideoComment::find($id);

        if (!$comment) {
            return response()->json(['res_type'=> 'Not found', 'message'=>'Comment not found.'],404);
        }

        $commData = [];

        if ($comment->replies->count() > 0) {
            //get replies
            $replyData = [];

            foreach ($comment->replies as $reply) {
                $replies = [
                    'id'            => $reply->id,
                    'video_comment_id'=> $comment->id,
                    'by'            => $reply->user->annon_name,
                    'reply_text'    => $reply->reply_text,
                    'created_at'    => $reply->created_at,
                ];
                array_push($replyData, $replies);
            }
        }else{
            $replyData = null;
        }

        $data = [
            'id'            => $comment->id,
            'by'            => $comment->user->annon_name,
            'comment_text'  => $comment->comment_text,
            'created_at'    => $comment->created_at,
            'replies'       => $replyData
        ];
        array_push($commData, $data);

        return response()->json(['res_type'=>'success', 'comment'=>$commData]);
    }

    public function likeComment($id)
    {
        $comment = VideoComment::find($id);

        if (!$comment) {
            return response()->json(['res_type'=>'not found', 'message'=>'Comment not found']);
        }

        $comment->likes = $comment->likes+1;
        $comment->save();

        return response()->json(['res_type'=>'success', 'message'=>'Comment liked']);
    }

    public function dislikeComment($id)
    {
        $comment = VideoComment::find($id);

        if (!$comment) {
            return response()->json(['res_type'=>'not found', 'message'=>'Comment not found']);
        }

        $comment->likes = $comment->dislikes+1;
        $comment->save();

        return response()->json(['res_type'=>'success', 'message'=>'Comment disliked']);
    }

    public function likeVideoCommtReply($id)
    {
        $reply = VidCommentReply::find($id);

        if (!$reply) {
            return response()->json(['res_type'=>'not found', 'message'=>'Reply not found']);
        }

        $reply->likes = $reply->likes+1;
        $reply->save();

        return response()->json(['res_type'=>'success', 'message'=>'Reply liked']);
    }

    public function dislikeVideoCommtReply($id)
    {
        $reply = VidCommentReply::find($id);

        if (!$reply) {
            return response()->json(['res_type'=>'not found', 'message'=>'Reply not found']);
        }

        $reply->likes = $reply->dislikes+1;
        $reply->save();

        return response()->json(['res_type'=>'success', 'message'=>'Reply disliked']);
    }
}
