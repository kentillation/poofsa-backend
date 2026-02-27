<?php

namespace App\Actions\Products;
use App\Repositories\ProductRepository;

class GetProductsHistoryAction
{
    public function __construct(private ProductRepository $repo) {}

    public function execute($shopId, $branchId, $search, $perPage)
    {
        return $this->repo->getProductsHistory($shopId, $branchId, $search, $perPage);
    }
}

// This Action is for fetching Products History module only