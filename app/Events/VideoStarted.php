<?php

namespace App\Events;

use App\Models\VideoCall;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
// use Illuminate\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class VideoStarted extends Event
{
   use InteractsWithSockets, SerializesModels;
   // use Dispatchable

	public $identity;

	public $call;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($identity, $call)
    {
        $this->identity = $identity;
        $this->call = $call;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
    	$channels = [];
    	$channels[] = new PrivateChannel('user_video_call_'.$this->call->user_id);
    	$channels[] = new PrivateChannel('vendor_video_call_'.$this->call->vendor_id);

        return $channels;
    }
}
