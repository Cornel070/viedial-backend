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

class ApptAccepted extends Event
{
	use InteractsWithSockets, SerializesModels;
   // use Dispatchable

    public $appt;

    public function __construct($appt)
    {
        $this->appt = $appt;
    }

    public function broadcastOn()
    {
    	return new PrivateChannel('appt_accepted_'.$this->appt->requestee_id);
    }
}
