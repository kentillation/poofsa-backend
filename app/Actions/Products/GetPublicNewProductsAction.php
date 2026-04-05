<?php

namespace App\Actions\Products;
use App\Repositories\PublicNewProductsRepository;

class GetPublicNewProductsAction
{
    public function __construct(private PublicNewProductsRepository $publicNewProductRepository) {}

    public function execute($isNew, $perPage, $search)
    {
        return $this->publicNewProductRepository->getAllPublicNewProducts($isNew, $perPage, $search);
    }
}

// This Action is for fetching all public roducts module only