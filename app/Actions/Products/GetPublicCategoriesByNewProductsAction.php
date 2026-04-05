<?php

namespace App\Actions\Products;

use App\Repositories\PublicNewProductsRepository;

class GetPublicCategoriesByNewProductsAction
{
    public function __construct(private PublicNewProductsRepository $publicNewProductAndCategoryRepository) {}

    public function execute($isNew, $perPage, $search)
    {
        return $this->publicNewProductAndCategoryRepository->getAllCategoriesByNewProducts($isNew, $perPage, $search);
    }
}

// This Action is for fetching all public roducts module only