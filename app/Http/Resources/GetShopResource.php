<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GetShopResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        $allProducts = $this->branches->flatMap(function ($branch) {
            return $branch->products;
        });

        $lowestProduct = $allProducts->sortBy('base_price')->first();
        $branchId = $this->determineBranchId($lowestProduct);
        $selectedBranch = $this->branches->firstWhere('branch_id', $branchId);

        return [
            'shop_id' => $this->shop_id,
            'branch_id' => $branchId,
            'shop_name' => $this->shop_name,
            'shop_type' => $this->shop_type,
            'shop_image' => $this->thumbnail_url,
            'shop_address' => optional($selectedBranch)->branch_address,
            'branch_latitude' => optional($selectedBranch)->branch_latitude,
            'branch_longitude' => optional($selectedBranch)->branch_longitude,
            'open_at' => optional($selectedBranch)->open_at,
            'close_at' => optional($selectedBranch)->close_at,
            'has_products' => $allProducts->isNotEmpty(),
            'has_branches' => $this->branches->isNotEmpty(),
            'lowest_price' => $lowestProduct->base_price ?? null,
            'product_name' => $lowestProduct->product_name ?? null,
            'category_label' => $lowestProduct->category->category_label ?? null,
            'additional_info' => $this->when($request->has('include_details'), [
                'branches_count' => $this->branches->count(),
                'products_count' => $allProducts->count(),
            ]),
        ];
    }

    /**
     * Determine the appropriate branch ID.
     */
    protected function determineBranchId($lowestProduct)
    {
        if ($lowestProduct) {
            return $lowestProduct->branch_id;
        }

        if ($this->branches->isNotEmpty()) {
            return $this->branches->first()->branch_id;
        }

        return null;
    }

    /**
     * Customize the response for a collection.
     */
    public static function collection($resource)
    {
        return tap(parent::collection($resource), function ($collection) {
            $collection->additional([
                'meta' => [
                    'version' => '1.0.0',
                    'timestamp' => now()->toDateTimeString(),
                ]
            ]);
        });
    }
}