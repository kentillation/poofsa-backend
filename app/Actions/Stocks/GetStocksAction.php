<?php

namespace App\Actions\Stocks;
use App\Repositories\StockRepository;

class GetStocksAction
{
    public function __construct(private StockRepository $repo) {}

    public function execute($shopId, $branchId, $search, $perPage)
    {
        return $this->repo->getStocks($shopId, $branchId, $search, $perPage);
    }
}

// This Action is for fetching Stocks module only