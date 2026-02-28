<?php

namespace App\Actions\Orders;
use App\Repositories\OrderRepository;

class GetOrdersReportAction
{
    public function __construct(private OrderRepository $repo) {}

    public function execute($shopId, $branchId, $dateType, $perPage)
    {
        return $this->repo->getOrdersReport($shopId, $branchId, $dateType, $perPage);
    }
}

// This Action is for Orders Report module only