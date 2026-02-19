<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LowStockLevel implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $shopId;
    public $lowStockItems;

    public function __construct($shopId, $lowStockItems)
    {
        $this->shopId = $shopId;
        $this->lowStockItems = $lowStockItems;
    }

    public function broadcastWith(): array
    {
        return [
            'shop_id' => $this->shopId,
            'low_stock_items' => $this->lowStockItems,
            'count' => count($this->lowStockItems),
        ];
    }

    public function broadcastOn()
    {
        return [
            new Channel("lowStockLevelChannel.{$this->shopId}"),
        ];
    }
}
