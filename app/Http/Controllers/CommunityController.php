<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Reply;
use App\Models\Topic;

class CommunityController extends Controller
{
	public $user;

	public function __construct()
	{
		$this->user = auth()->user();
	}

	public function makePost(Request $request)
	{
		$validator = $this->validatePost($request);

        if ($validator->fails())
        {
            return response()->json(['res_type'=> 'validator_error', 'errors'=>$validator->errors()->all()],422);
        }

        $post = new Post;
        $post->topic = $request->topic;  //topics are: Type 2 Diabetes, Meals, Hypertension, CVD, Fitness, Physical Activity
        $post->user_id = $this->user->id;
        $post->post_text = $request->post_text;
        $post->save();

        return response()->json(['res_type'=>'success', 'message'=>'posted']);
	}

	public function validatePost(Request $request)
    {
        $msg = [
            'post_text.required' => 'Please enter post text',
            'topic.required'	 => 'Please select a topic for the post',
            'topic.string'		 => 'The topic must be a valid text'
        ];
        return validator()->make($request->all(), [
            'post_text' => 'required',
            'topic'		=> 'required|string'
        ], $msg);
    }

    public function getTopics()
    {
        $topics = Topic::all();

        if ($topics->isEmpty()) {
            return response()->json(['res_type'=>'not found', 'message'=>'No topics found'],404);
        }

        return response()->json(['res_type'=>'success', 'topics'=>$topics]);
    }

    public function allPosts()
    {
    	$posts = Post::all();

    	if ($posts->isEmpty()) {
    		return response()->json(['res_type'=> 'Not found', 'message'=>'No posts yet.'],404);
    	}

    	$postData = [];

    	foreach ($posts as $post) {
    		$data = [
    			'id'			=> $post->id,
    			'topic' 		=> $post->topic,
    			'by'			=> $post->user->annon_name,
    			'post_text'		=> $post->post_text,
    			'comments_count'=> $post->comments->count(),
    			'created_at'	=>$post->created_at
    		];
    		array_push($postData, $data);
    	}

    	return response()->json(['res_type'=>'success', 'posts'=>$postData]);
    }

     public function singlePost($id)
    {
    	$post = Post::find($id);

    	if (!$post) {
    		return response()->json(['res_type'=> 'Not found', 'message'=>'Post not found.'],404);
    	}

    	$postData = [];

    	if ($post->comments->count() > 0) {
    		//get comments
	    	$commData = [];

	    	foreach ($post->comments as $comm) {
	    		$comms = [
	    			'id'			=> $comm->id,
	    			'post_id'		=> $post->id,
	    			'by'			=> $comm->user->annon_name,
	    			'comment_text'	=> $comm->comment_text,
	    			'created_at'	=> $comm->created_at,
	    		];
	    		array_push($commData, $comms);
	    	}
    	}else{
    		$commData = 'No comments';
    	}

    	$data = [
    		'id'			=> $post->id,
    		'topic' 		=> $post->topic,
    		'category'		=> $post->category,
    		'by'			=> $post->user->annon_name,
    		'post_text'		=> $post->post_text,
    		'comments'		=> $commData,
    		'created_at'	=>$post->created_at
    	];
    	array_push($postData, $data);

    	return response()->json(['res_type'=>'success', 'post'=>$postData]);
    }

    public function commentOnPost(Request $request, $id)
    {
    	$validator = $this->validateComment($request);

        if ($validator->fails())
        {
            return response()->json(['res_type'=> 'validator_error', 'errors'=>$validator->errors()->all()],422);
        }

        $comment = new Comment;
        $comment->comment_text = $request->comment_text;
        $comment->post_id = $id;
        $comment->user_id = $this->user->id;
        $comment->save();

        return response()->json(['res_type'=>'success', 'message'=>'commented']);
    }

    public function validateComment(Request $request)
    {
        $msg = [
            'comment_text.required' => 'Please enter a comment',
            'comment_text.string'		 => 'The comment must be a valid text'
        ];
        return validator()->make($request->all(), [
            'comment_text' => 'required|string',
        ], $msg);
    }

    public function getPostComments($id)
    {	
    	$post = Post::find($id);

    	if (!$post) {
    		return response()->json(['res_type'=>'Not found', 'message'=>'Post not found.'], 404);
    	}

    	if ($post->comments->count() > 0) {
    		//get comments
	    	$commData = [];

	    	foreach ($post->comments as $comm) {
	    		$comms = [
	    			'id'			=> $comm->id,
	    			'post_id'		=> $post->id,
	    			'by'			=> $comm->user->annon_name,
	    			'comment_text'	=> $comm->comment_text,
	    			'replies_count'	=> $comm->replies->count(),
	    			'created_at'	=> $comm->created_at,
	    		];
	    		array_push($commData, $comms);
	    	}
	    	return response()->json(['res_type'=>'success', 'comments'=>$commData]);
    	}else{
    		return response()->json(['res_type'=>'not found', 'message'=>'No comments for this post.'], 404);
    	}
    }

    public function sendReply(Request $request, $id)
    {
    	$validator = $this->validateReply($request);

        if ($validator->fails())
        {
            return response()->json(['res_type'=> 'validator_error', 'errors'=>$validator->errors()->all()],422);
        }

        $reply = new Reply;
        $reply->comment_id = $id;
        $reply->user_id = $this->user->id;
        $reply->reply_text = $request->reply_text;
        $reply->save();

        return response()->json(['res_type'=>'success', 'message'=>'Replied']);
    }

    public function validateReply(Request $request)
    {
        $msg = [
            'reply_text.required' => 'Please enter a reply',
            'reply_text.string'		 => 'The reply must be a valid text'
        ];
        return validator()->make($request->all(), [
            'reply_text' => 'required|string',
        ], $msg);
    }

    public function singleComment($id)
    {
    	$comment = Comment::find($id);

    	if (!$comment) {
    		return response()->json(['res_type'=> 'Not found', 'message'=>'Comment not found.'],404);
    	}

    	$commData = [];

    	if ($comment->replies->count() > 0) {
	    	//get replies
		    $replyData = [];

		    foreach ($comment->replies as $reply) {
		    	$replies = [
		    		'id'			=> $reply->id,
		    		'comment_id'	=> $comment->id,
		    		'by'			=> $reply->user->annon_name,
		    		'reply_text'	=> $reply->reply_text,
		    		'created_at'	=> $reply->created_at,
		    	];
		    	array_push($replyData, $replies);
		    }
	    }else{
	    	array_push($replyData, 'No replies');
	    }

    	$data = [
    		'id'			=> $comment->id,
    		'by'			=> $comment->user->annon_name,
    		'comment_text'	=> $comment->comment_text,
    		'replies'		=> $replyData,
    		'created_at'	=> $comment->created_at
    	];
    	array_push($commData, $data);

    	return response()->json(['res_type'=>'success', 'comment'=>$commData]);
    }
}
