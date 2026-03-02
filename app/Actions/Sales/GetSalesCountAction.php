<?php

namespace App\Actions\Sales;
use App\Repositories\SaleRepository;

class GetSalesCountAction
{
    public function __construct(private SaleRepository $repo) {}

    public function execute($shopId, $branchId)
    {
        return $this->repo->getTotalSales($shopId, $branchId);
    }
}

// This Action is for Sales count only
