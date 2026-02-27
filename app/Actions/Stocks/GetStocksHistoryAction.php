<?php

namespace App\Actions\Stocks;
use App\Repositories\StockRepository;

class GetStocksHistoryAction
{
    public function __construct(private StockRepository $repo) {}

    public function execute($shopId, $branchId, $search, $perPage)
    {
        return $this->repo->getStocksHistory($shopId, $branchId, $search, $perPage);
    }
}

// This Action is for fetching Stocks History module only