<?php

namespace App\Events;

use App\Models\Appt;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
// use Illuminate\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ApptDeclined extends Event
{
	use InteractsWithSockets, SerializesModels;
   // use Dispatchable

    public $appt;

    public $reason;

    public function __construct($appt, $reason)
    {
        $this->appt = $appt;
        $this->reason = $reason;
    }

    public function broadcastOn()
    {
    	return new PrivateChannel('appt_declined_'.$this->appt->requestee_id);
    }
}
