<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Chat;
use App\Events\MessageSent;
use App\Models\User;
use App\Models\Message;

class ChatController extends Controller
{
    public function sendDirectChat(Request $request)
    {
    	$validator = $this->validator($request);

        if ($validator->fails())
        {
            return response()->json(['res_type'=> 'validator_error', 'errors'=>$validator->errors()->all()]);
        }

        $chat_id = $request->chat_id;

        if (!$chat_id) {
            $chat = New Chat;
            $chat->user1_id = $request->from_id;
            $chat->user1_name = $request->from_name;
            $chat->user2_id = $request->to_id;
            $chat->user2_name = $request->to_name;
            $chat->save();

            $chat_id  = $chat->id;
        }

        $message = new Message;
        $message->from_id = $request->from_id;
        $message->from_name = $request->from_name;
        $message->to_id = $request->to_id;
        $message->to_name = $request->to_name;
        $message->message_text = $request->message_text;
        $message->chat_id = $chat_id;
        $message->save();

        broadcast(new MessageSent($request->from_name, $message))->toOthers();

        return response()->json(['res_type'=>'success', 'message'=>$message]);
    }

    public function validator(Request $request)
    {
        return validator()->make($request->all(), [
            'to_id'   => 'required|integer',
            'message_text'   => 'required',
        ]);
    }

    public function viewAllChats($user_id)
    {
    	$userChats = Chat::where('user1_id', $user_id)->orWhere('user2_id', $user_id)->get();

    	if ($userChats->isEmpty()) {
    		return response()->json(['res_type'=>'error', 'message'=>'No chats found']);
    	}

    	$chatData = array();

    	foreach ($userChats as $chat) {
            // Loop through the messages to get the last one 
            $last = count($chat->messages);
            $i = 0;
            foreach ($chat->messages as $key => $value) {
                $i++;
                $lastMsg = '';
                if ($i === $last) {
                    $lastMsg = $value->message_text;
                }
            }
    		$data = [
    			'id' => $chat->id,
    			'user_1_id' => $chat->user1_id,
                'user_1_name' => $chat->user1_name,
                'user_2_id' => $chat->user2_id,
                'user_2_name' => $chat->user2_name,
    			'last_message' => $lastMsg,
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
            return response()->json(['res_type'=>'error', 'message'=>'Chat does not exist']);
        }

        if (!$chat->messages) {
            return response()->json(['res_type'=>'error', 'message'=>'No messages found']);
        }

        return response()->json(['res_type'=>'success', 'message'=>$chat->messages]);
    }

    /* 
        The W parameter indicates whether the request is from within the code (another function)
    /   Or from outside (the gateway)
    */
    public function deleteMessage($id, $w = false)
    {
        $msg = Message::find($id);

        if (!$msg) {
            return response()->json(['res_type'=>'error', 'message'=>'Message does not exist']);
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
            return response()->json(['res_type'=>'error', 'message'=>'Chat does not exist']);
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
