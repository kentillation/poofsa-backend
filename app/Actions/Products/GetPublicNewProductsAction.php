<?php

namespace App\Actions\Products;
use App\Repositories\PublicProductsAndCategoriesRepository;

class GetPublicNewProductsAction
{
    public function __construct(private PublicProductsAndCategoriesRepository $repository) {}

    public function execute($isNew, $perPage, $search)
    {
        return $this->repository->getAllPublicNewProducts($isNew, $perPage, $search);
    }
}

// This Action is for fetching all public new products module only