<?php

namespace App\Actions\Products;
use App\Repositories\ProductRepository;

class GetProductsAction
{
    public function __construct(private ProductRepository $repository) {}

    public function execute($shopId, $branchId, $search, $perPage)
    {
        return $this->repository->getProducts($shopId, $branchId, $search, $perPage);
    }
}

// This Action is for fetching products module only