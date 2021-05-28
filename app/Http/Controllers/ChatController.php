<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Chat;
use App\Events\MessageSent;
use App\Models\User;
use App\Models\Message;

class ChatController extends Controller
{
    protected $user;

    public function __construct()
    {
        $this->user = auth()->user();
    }

    public function sendDirectChat(Request $request)
    {
    	$validator = $this->validator($request);

        if ($validator->fails())
        {
            return response()->json(['res_type'=> 'validator_error', 'errors'=>$validator->errors()->all()],422);
        }

        $chat_id = $request->chat_id;
        $to_user = User::find($request->to_id);

        if (!$to_user) {
            return response()->json(['res_type'=>'not found', 'message'=>'Recipient user does not exist'],404);
        }

        if (!$chat_id) {
            $chat = New Chat;
            $chat->user1_id = $this->user->id;
            $chat->user1_name = $this->user->name;
            $chat->user2_id = $to_user->id;
            $chat->user2_name = $to_user->name;
            $chat->save();

            $chat_id  = $chat->id;
        }

        $message = new Message;
        $message->from_id = $this->user->id;
        $message->from_name = $this->user->name;
        $message->to_id = $to_user->id;
        $message->to_name = $to_user->name;
        $message->message_text = $request->message_text;
        $message->chat_id = $chat_id;
        $message->save();

        broadcast(new MessageSent($message->from_name, $message))->toOthers();

        return response()->json(['res_type'=>'success', 'message'=>$message]);
    }

    public function validator(Request $request)
    {
        $msg = [
            'to_id.required'        => 'Please select a recipient',
            'message_text.required' => 'Please enter a message',
        ];
        return validator()->make($request->all(), [
            'to_id'   => 'required',
            'message_text'   => 'required',
        ], $msg);
    }

    public function viewAllChats()
    {
    	$userChats = Chat::where('user1_id', $this->user->id)->orWhere('user2_id', $this->user->id)->get();

    	if ($userChats->isEmpty()) {
    		return response()->json(['res_type'=>'no content', 'message'=>'No chats found']);
    	}

    	$chatData = array();

    	foreach ($userChats as $chat) {
            // Loop through the messages to get the last one 
            $last = count($chat->messages);
            $i = 0;
            $unreadChats = 0;
            foreach ($chat->messages as $key => $value) {
                $i++;
                $lastMsg = '';
                if ($i === $last) {
                    $lastMsg = $value->message_text;
                }

                if ($value->to_id == $this->user->id && $value->status === 'unread') {
                    $unreadChats = $unreadChats + 1; 
                }
            }
    		$data = [
    			'id' => $chat->id,
    			'user_1_id' => $chat->user1_id,
                'user_1_name' => $chat->user1_name,
                'user_2_id' => $chat->user2_id,
                'user_2_name' => $chat->user2_name,
    			'last_message' => $lastMsg,
                'unread_msg_count' => $unreadChats,
    			'created_at' => $chat->created_at,
    		];

    		array_push($chatData, $data);
    	}

    	return response()->json(['res_type'=>'success', 'chats'=>$chatData]);
    }

    public function allChatMessages($id) 
    {
        $chat = Chat::find($id);

        if (!$chat) {
            return response()->json(['res_type'=>'not found', 'message'=>'Chat does not exist'],404);
        }

        if (!$chat->messages) {
            return response()->json(['res_type'=>'no content', 'message'=>'No messages yet']);
        }

        return response()->json(['res_type'=>'success', 'messages'=>$chat->messages]);
    }

    public function allDoctors()
    {
        $doctors = User::where('role', '!=', 'Client')->select('id','name','gender','role')->get();

        if ($doctors->isEmpty()) {
            return response()->json(['res_type'=>'no content', 'message'=>'No health personels registered yet.']);
        }

        return response()->json(['res_type'=>'success', 'recipients'=>$doctors]);
    }

    public function checkPrevChat($user_id)
    {
        $chat = Chat::where('user1_id', $user_id)->orWhere('user2_id', $user_id)->first();

        if (!$chat) {
            return response()->json(['res_type'=>'no content', 'message'=>'No previous chat found']);
        }

        return response()->json(['res_type'=>'success', 'messages'=>$chat->messages]);
    }

    public function markChatAsRead($id)
    {
        $chat = Chat::find($id);

        if (!$chat) {
            return response()->json(['res_type'=>'not found', 'message'=>'Chat does not exist'],404);
        }

        if ($chat->messages->isEmpty()) {
            return response()->json(['res_type'=>'success']); 
        }

        foreach ($chat->messages as $msg) {
            if ($msg->to_id == $this->user->id) {
                $msg->status = 'read';
                $msg->save();
            }
        }

        return response()->json(['res_type'=>'success']); 
    }

    public function markMsgAsRead($id)
    {
        $msg = Message::find($id);

        if (!$msg) {
            return response()->json(['res_type'=>'not found', 'message'=>'Message does not exist'],404);
        }

        if ($msg->to_id == $this->user->id) {
            $msg->status = 'read';
            $msg->save();
        }

        return response()->json(['res_type'=>'success']); 
    }

    /* 
        The W parameter indicates whether the request is from within the code (another function)
    /   Or from outside (the gateway)
    */
    public function deleteMessage($id, $w = false)
    {
        $msg = Message::find($id);

        if (!$msg) {
            return response()->json(['res_type'=>'not found', 'message'=>'Message does not exist'],404);
        }

        $msg->delete();

        if ($w) {

            return true;
        }

        return response()->json(['res_type'=>'success', 'message'=>'Message deleted']);
    }

    public function deleteChat($id, $w = false)
    {
        $chat = Chat::find($id);

        if (!$chat) {
            return response()->json(['res_type'=>'not found', 'message'=>'Chat does not exist'],404);
        }

        $chat->delete();

        if ($w) {

            return true;
        }

        return response()->json(['res_type'=>'success', 'message'=>'Chat deleted']);
    }

    public function multiDeleteMsgs(Request $request)
    {
        $msgs = $request->messages;

        for ($i = 0; $i < count($msgs); $i++) { 
            $this->deleteMessage($msgs[$i], true);
        }

        return response()->json(['res_type'=>'success', 'message'=>'Messages deleted']);
    }

    public function multiDeleteChats(Request $request)
    {
        $chats = $request->chats;

        for ($i = 0; $i < count($chats); $i++) { 
            $this->deleteChat($chats[$i], true);
        }

        return response()->json(['res_type'=>'success', 'message'=>'Chats deleted']);
    }
}
