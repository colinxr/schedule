<?php

namespace App\Support;

use Illuminate\Support\Collection;

class TimeslotPaginator
{
    /**
     * Paginate an array of timeslots
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
        $totalPages = $total > 0 ? max(1, ceil($total / $perPage)) : 0;
        $page = min($page, max(1, $totalPages));
        
        $offset = ($page - 1) * $perPage;
        $items = $collection->slice($offset, $perPage)->values();

        return [
            'data' => $items->all(),
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
                'has_more_pages' => $page < $totalPages,
                'from' => $total > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $total)
            ]
        ];
    }
} 