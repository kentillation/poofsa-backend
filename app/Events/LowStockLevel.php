<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
// use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LowStockLevel implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $shopId;
    public $branchId;
    public $lowStockItems;

    public function __construct($shopId, $branchId, $lowStockItems)
    {
        $this->shopId = $shopId;
        $this->branchId = $branchId;
        $this->lowStockItems = $lowStockItems;
    }

    public function broadcastWith(): array
    {
        return [
            'shop_id' => $this->shopId,
            'branch_id' => $this->branchId,
            'low_stock_items' => $this->lowStockItems,
            'count' => count($this->lowStockItems),
        ];
    }

    public function broadcastOn()
    {
        return [
            new Channel("lowStockLevelChannel.{$this->shopId}.{$this->branchId}"),
        ];
        // return new PrivateChannel("branch.{$this->shopId}.{$this->branchId}");
    }
}
