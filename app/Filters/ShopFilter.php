<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ShopFilter
{
    protected $request;
    protected $filters = [];

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->filters = [
            'category' => $request->input('requested_category'),
            'meal_type' => $request->input('requested_meal_type'),
            'time_between' => $request->input('requested_time_between'),
        ];
    }

    /**
     * Apply all filters to the query.
     */
    public function apply(Builder $query): Builder
    {
        if ($this->hasAnyFilters()) {
            $query->whereHas('branches.products', function ($q) {
                $this->applyProductFilters($q);
            });
        }

        return $query;
    }

    /**
     * Apply product-level filters.
     */
    protected function applyProductFilters(Builder $query): void
    {
        $query->where('availability_id', 1);

        if ($this->filters['category']) {
            $query->whereHas('category', function ($cat) {
                $cat->where('category_label', $this->filters['category']);
            });
        }

        if ($this->filters['meal_type']) {
            $query->whereHas('category.baseCategory', function ($base) {
                $base->whereRaw('JSON_CONTAINS(meal_type, ?)', [
                    json_encode($this->filters['meal_type'])
                ]);
            });
        }

        if ($this->filters['time_between']) {
            $this->applyTimeFilter($query);
        }
    }

    /**
     * Apply time-based filter on branches.
     */
    protected function applyTimeFilter(Builder $query): void
    {
        $time = $this->filters['time_between'];
        
        $query->whereHas('branch', function ($branchQuery) use ($time) {
            $this->applyBranchTimeFilter($branchQuery, $time);
        });
    }

    /**
     * Apply branch time filter logic.
     */
    protected function applyBranchTimeFilter(Builder $query, string $time): void
    {
        $query->where(function ($query) use ($time) {
            $query->where(function ($q0) {
                $q0->whereColumn('open_at', '=', 'close_at');
            })
            ->orWhere(function ($q1) use ($time) {
                $q1->where('is_overnight', 0)
                    ->whereTime('open_at', '<=', $time)
                    ->whereTime('close_at', '>=', $time);
            })
            ->orWhere(function ($q2) use ($time) {
                $q2->where('is_overnight', 1)
                    ->where(function ($q3) use ($time) {
                        $q3->whereTime('open_at', '<=', $time)
                            ->orWhereTime('close_at', '>=', $time);
                    });
            });
        });
    }

    /**
     * Get branch time filter for eager loading.
     */
    public function getBranchTimeFilter(): ?\Closure
    {
        if (!$this->filters['time_between']) {
            return null;
        }

        $time = $this->filters['time_between'];
        
        return function ($branchQuery) use ($time) {
            $branchQuery->where(function ($query) use ($time) {
                $query->where(function ($q0) {
                    $q0->whereColumn('open_at', '=', 'close_at');
                })
                ->orWhere(function ($q1) use ($time) {
                    $q1->where('is_overnight', 0)
                        ->whereTime('open_at', '<=', $time)
                        ->whereTime('close_at', '>=', $time);
                })
                ->orWhere(function ($q2) use ($time) {
                    $q2->where('is_overnight', 1)
                        ->where(function ($q3) use ($time) {
                            $q3->whereTime('open_at', '<=', $time)
                                ->orWhereTime('close_at', '>=', $time);
                        });
                });
            });
        };
    }

    /**
     * Check if any filters are present.
     */
    public function hasAnyFilters(): bool
    {
        return !empty(array_filter($this->filters));
    }

    /**
     * Get cache key for the current filters.
     */
    public function getCacheKey(): string
    {
        $filterString = http_build_query(array_filter($this->filters));
        return 'shops_' . md5($filterString);
    }
}