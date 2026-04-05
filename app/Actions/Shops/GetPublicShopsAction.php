<?php

namespace App\Actions\Shops;
use App\Repositories\PublicShopsRepository;

class GetPublicShopsAction
{
    public function __construct(private PublicShopsRepository $publicShopRepository) {}

    public function execute($categoryLabel, $mealType, $timeBetween, $perPage, $search)
    {
        return $this->publicShopRepository->getAllPublicShops($categoryLabel, $mealType, $timeBetween, $perPage, $search);
    }
}

// This Action is for fetching all public shops module only