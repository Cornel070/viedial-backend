<?php

namespace App\Events;

use App\Models\Chat;
use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
// use Illuminate\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class MessageSent extends Event
{
	use InteractsWithSockets, SerializesModels;
   // use Dispatchable
        /**
     * User that sent the message
     *
     * @var User
     */
    public $from;

    /**
     * Message details
     *
     * @var Message
     */
    public $message;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($from, Message $message)
    {
        $this->from = $from;
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
    	$channels = [];
    	$channels[] = new PrivateChannel('user_chat_'.$message->from_id);
    	$channels[] = new PrivateChannel('user_chat_'.$message->to_id);

        return $channels;
    }
}