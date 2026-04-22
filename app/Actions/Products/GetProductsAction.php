<?php

namespace App\Actions\Products;
use App\Repositories\ProductRepository;

class GetProductsAction
{
    public function __construct(private ProductRepository $repository) {}

    public function execute($shopId, $branchId, $perPage, $search)
    {
        return $this->repository->getProducts($shopId, $branchId, $perPage, $search);
    }
}

// This Action is for fetching products module only
