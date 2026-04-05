<?php

namespace App\Actions\Products;
use App\Repositories\PublicProductsRepository;

class GetPublicProductsAction
{
    public function __construct(private PublicProductsRepository $repository) {}

    public function execute($shopId, $branchId, $perPage, $search)
    {
        return $this->repository->getAllPublicProducts($shopId, $branchId, $perPage, $search);
    }
}

// This Action is for fetching all public products module only