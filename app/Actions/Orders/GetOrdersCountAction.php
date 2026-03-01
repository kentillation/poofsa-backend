<?php

namespace App\Actions\Orders;
use App\Repositories\OrderRepository;

class GetOrdersCountAction
{
    public function __construct(private OrderRepository $repo) {}

    public function execute($shopId, $branchId)
    {
        return $this->repo->getOrdersCount($shopId, $branchId);
    }
}

// This Action is for Orders Count module only