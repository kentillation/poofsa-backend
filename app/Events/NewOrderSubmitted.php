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

    // public $shopId;
    // public $branchId;
    // public $orderData;
    public $message;

    public function __construct($message)
    {
        // $this->shopId = $shopId;
        // $this->branchId = $branchId;
        // $this->orderData = $orderData;
        $this->message = $message;
    }

    public function broadcastWith(): array
    {
        return [
            'message' => $this->message,
        ];
    }

    public function broadcastOn()
    {
        return [
            new Channel('newOrderChannel'),
        ];
    }
}