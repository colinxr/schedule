<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class TimeslotPaginator extends LengthAwarePaginator
{
    /**
     * Create a new paginator for time slots.
     *
     * @param array $slots
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public static function paginate(array $slots, int $page = 1, int $perPage = 10): array
    {
        $collection = Collection::make($slots);
        
        $total = $collection->count();
        
        // Get items for current page
        $items = $collection->forPage($page, $perPage);
        
        $paginator = new static(
            $items,
            $total,
            $perPage,
            $page
        );

        return [
            'data' => $items->values()->all(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $paginator->lastPage(),
                'has_more_pages' => $paginator->hasMorePages(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem()
            ]
        ];
    }
} 