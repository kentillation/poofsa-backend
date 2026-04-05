<?php

namespace App\Actions\Products;
use App\Repositories\PublicNewProductsRepository;

class GetPublicNewProductsAction
{
    public function __construct(private PublicNewProductsRepository $repository) {}

    public function execute($isNew, $perPage, $search)
    {
        return $this->repository->getAllPublicNewProducts($isNew, $perPage, $search);
    }
}

// This Action is for fetching all public new products module only