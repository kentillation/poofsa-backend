<?php

namespace App\Actions\Products;
use App\Repositories\PublicProductsByMealTypeRepository;

class GetPublicProductsByMealTypeAction
{
    public function __construct(private PublicProductsByMealTypeRepository $publicProductRepository) {}

    public function execute($mealType, $perPage)
    {
        return $this->publicProductRepository->getAllPublicProductsByMealType($mealType, $perPage);
    }
}

// This Action is for fetching all public products based on meal type with pagination and search functionality