<?php

namespace App\Actions\Products;
use App\Repositories\PublicProductsByMealTypeRepository;

class GetPublicProductsByMealTypeAction
{
    public function __construct(private PublicProductsByMealTypeRepository $repository) {}

    public function execute($mealType, $perPage, $search)
    {
        return $this->repository->getAllPublicProductsByMealType($mealType, $perPage, $search);
    }
}

// This Action is for fetching all public products by meal type only