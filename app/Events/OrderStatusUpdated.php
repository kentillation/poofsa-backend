<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $orderStatusMessage;
    public $stationId;

    public function __construct($orderStatusMessage, $stationId)
    {
        $this->orderStatusMessage = $orderStatusMessage;
        $this->stationId = $stationId;
    }

    public function broadcastWith(): array
    {
        return [
            'message' => $this->orderStatusMessage,
            'stationId' => $this->stationId,
        ];
    }

    public function broadcastOn()
    {
        // return [ new Channel('orderStatusChannel'), ];
        return new Channel('station.' . $this->stationId);
    }
}
