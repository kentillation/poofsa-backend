<?php

namespace App\Actions\Products;
use App\Repositories\PublicProductsRepository;

class GetPublicProductsAction
{
    public function __construct(private PublicProductsRepository $publicProductRepository) {}

    public function execute($shopId, $branchId, $search, $perPage)
    {
        return $this->publicProductRepository->getAllPublicProducts($shopId, $branchId, $search, $perPage);
    }
}

// This Action is for fetching all public roducts module only