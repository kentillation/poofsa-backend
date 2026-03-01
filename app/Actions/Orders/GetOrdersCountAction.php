<?php

namespace App\Actions\Orders;
use App\Repositories\OrderRepository;

class GetOrdersCountAction
{
    public function __construct(private OrderRepository $repo) {}

    public function execute($shopId, $branchId, $dateType)
    {
        return $this->repo->getOrdersCount($shopId, $branchId, $dateType);
    }
}

// This Action is for Orders Count module only