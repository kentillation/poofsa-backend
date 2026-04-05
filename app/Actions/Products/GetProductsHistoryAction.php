<?php

namespace App\Actions\Products;
use App\Repositories\ProductRepository;

class GetProductsHistoryAction
{
    public function __construct(private ProductRepository $repository) {}

    public function execute($shopId, $branchId, $search, $perPage)
    {
        return $this->repository->getProductsHistory($shopId, $branchId, $search, $perPage);
    }
}

// This Action is for fetching products history module only