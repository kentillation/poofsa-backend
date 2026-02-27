<?php

namespace App\Actions\Orders;
use App\Repositories\OrderRepository;

class GetOrdersAction
{
    public function __construct(private OrderRepository $repo) {}

    public function execute($shopId, $branchId, $search, $perPage)
    {
        return $this->repo->getOrders($shopId, $branchId, $search, $perPage);
    }
}

// This Action is for Orders module only