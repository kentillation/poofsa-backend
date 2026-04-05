<?php

namespace App\Actions\Products;

use App\Repositories\PublicProductsAndCategoriesRepository;

class GetPublicProductCategoriesAction
{
    public function __construct(private PublicProductsAndCategoriesRepository $repository) {}

    public function execute($shopId, $branchId, $perPage, $search)
    {
        return $this->repository->getAllProductCategories($shopId, $branchId, $perPage, $search);
    }
}

// This Action is for fetching all public products categories module only