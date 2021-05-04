<?php
namespace App\Services;

use Illuminate\Http\Request;
use App\Traits\RequestService;
use App\Models\User;

class CommService
{
    use RequestService;
    public $baseUri;
    public $secret;
    public $user;
    public function __construct()
    {
        $this->baseUri = config('services.comm.base_uri');
        $this->secret = config('services.comm.secret');
        $this->user = auth()->user();
    }

    public function createVidGroup(Request $request)
    {
        $data = array_merge($request->input(), ['user_id'=>$this->user->id]);
        return $this->request('POST', '/api/create-vid-meeting', $data);
    }

    public function joinVidCall($roomName)
    {
        return $this->request('GET', '/api/join-vid-meeting/'.$roomName.'/'.$this->user->name);
    }

    public function makeVidCall($vendor_id)
    {
        return $this->request('GET', '/api/make-vid-call/'.$this->user->id.'/'.$vendor_id.'?identity='.$this->user->name);
    }

    public function makeVioceCall($number)
    {
        return $this->request('GET', '/api/voice-call/'.$number);
    }

    public function sendChat($request)
    {
        $to = User::find($request->to_id);
        $data = array_merge($request->input(), ['from_name'=>$this->user->name, 'to_name'=>$to->name, 'from_id'=>$this->user->id]);
        return $this->request('POST', '/api/send-chat', $data);
    }

    public function allChats($user_id)
    {
        return $this->request('GET', '/api/view-chats/'.$user_id);
    }

    public function chatMessages($id)
    {
        return $this->request('GET', '/api/chat/'.$id.'/messages');
    }

    public function deleteMessage($id)
    {
        return $this->request('GET', '/api/delete-message/'.$id);
    }

    public function deleteChat($id)
    {
        return $this->request('GET', '/api/delete-chat/'.$id);
    }

    public function multiDeleteMSg(Request $request)
    {
        return $this->request('POST', '/api/multi-delete-msgs/', $request->messages);
    }

    public function multiDeleteChats(Request $request)
    {
        return $this->request('POST', '/api/multi-delete-chats/', $request->chats);
    }

    public function setAppt(Request $request)
    {
        return $this->request('POST', '/api/set-appt', $request->input());
    }
}