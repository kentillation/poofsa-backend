<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewOrderSubmitted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $stationId;

    public function __construct($message, $stationId)
    {
        $this->message = $message;
        $this->stationId = $stationId;
    }

    public function broadcastWith(): array
    {
        return [
            'message' => $this->message,
            'stationId' => $this->stationId,
        ];
    }

    public function broadcastOn()
    {
        // return [ new Channel('newOrderChannel'), ];
        return new Channel('station.' . $this->stationId);
    }
}
