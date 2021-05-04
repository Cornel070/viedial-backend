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

class Appointment extends Event
{
	use InteractsWithSockets, SerializesModels;
   // use Dispatchable
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public $appt;

    public function __construct($appt)
    {
        $this->appt = $appt;
    }

    public function broadcastOn()
    {
    	return new PrivateChannel('appt_'.$this->appt->recieved_by);
    }
}
