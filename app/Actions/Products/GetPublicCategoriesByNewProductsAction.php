<?php

namespace App\Actions\Products;

use App\Repositories\PublicNewProductsRepository;

class GetPublicCategoriesByNewProductsAction
{
    public function __construct(private PublicNewProductsRepository $repository) {}

    public function execute($isNew, $perPage, $search)
    {
        return $this->repository->getAllCategoriesByNewProducts($isNew, $perPage, $search);
    }
}

// This Action is for fetching all public products categories by new products module only