<?php

namespace App\Actions\Shops;
use App\Repositories\PublicShopsRepository;

class GetPublicShopsAction
{
    public function __construct(private PublicShopsRepository $publicShopRepository) {}

    public function execute($categoryLabel, $mealType, $timeBetween, $perPage = 10, $search = null)
    {
        return $this->publicShopRepository->getAllPublicShops($categoryLabel, $mealType, $timeBetween, $perPage ?? 10, $search ?? null);
    }
}

// This Action is for fetching all public shops module only