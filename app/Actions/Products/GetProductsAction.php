<?php

namespace App\Actions\Products;
use App\Repositories\ProductRepository;

class GetProductsAction
{
    public function __construct(private ProductRepository $repo) {}

    public function execute($shopId, $branchId, $search, $perPage)
    {
        return $this->repo->getProducts($shopId, $branchId, $search, $perPage);
    }
}

// This Action is for fetching Products module only