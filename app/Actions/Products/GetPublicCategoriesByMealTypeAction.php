<?php

namespace App\Actions\Products;
use App\Repositories\PublicCategoriesByMealTypeRepository;

class GetPublicCategoriesByMealTypeAction
{
    public function __construct(private PublicCategoriesByMealTypeRepository $repository) {}

    public function execute($mealType, $perPage, $search)
    {
        return $this->repository->getAllPublicCategoriesByMealType($mealType, $perPage, $search);
    }
}

// This Action is for fetching all public product categories by meal type only