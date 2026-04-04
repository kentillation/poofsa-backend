<?php

namespace App\Actions\Products;
use App\Repositories\PublicProductsRepository;

class GetPublicProductsAction
{
    public function __construct(private PublicProductsRepository $publicProductRepository) {}

    public function execute($shopId, $branchId, $perPage, $search)
    {
        return $this->publicProductRepository->getAllPublicProducts($shopId, $branchId, $perPage, $search);
    }
}

// This Action is for fetching all public roducts module only