<?php

namespace App\Actions\Products;
use App\Repositories\ProductRepository;

class GetProductsHistoryAction
{
    public function __construct(private ProductRepository $repository) {}

    public function execute($shopId, $branchId, $perPage, $search)
    {
        return $this->repository->getProductsHistory($shopId, $branchId, $perPage, $search);
    }
}

// This Action is for fetching products history module only