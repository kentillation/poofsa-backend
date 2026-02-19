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

    public $branchId;
    public $shopId;
    public $lowStockItems;

    /**
     * @param int $shopId
     * @param int $branchId
     * @param array $lowStockItems
     */
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

    public function broadcastOn(): array
    {
        // You can create branch-specific channels for Vue 3
        return [
            new Channel("lowStockLevelChannel.{$this->shopId}.{$this->branchId}"),
        ];
    }
}
