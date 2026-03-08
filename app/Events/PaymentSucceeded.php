<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentSucceeded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $referenceNumber;

    public function __construct($referenceNumber)
    {
        $this->referenceNumber = $referenceNumber;
    }

    public function broadcastOn()
    {
        // return new Channel('payments');
        return new PrivateChannel('payments.' . $this->referenceNumber);
    }
}
