<?php

namespace App\Support;

class TimeslotPaginator
{
    public static function paginate(array $items, int $page = 1, int $perPage = 10): array
    {
        $total = count($items);
        $totalPages = $total > 0 ? ceil($total / $perPage) : 1;
        $page = min($page, $totalPages);

        $offset = ($page - 1) * $perPage;
        $items = array_slice($items, $offset, $perPage);

        return [
            'data' => $items,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages,
                'has_more_pages' => $page < $totalPages,
                'from' => $total > 0 ? $offset + 1 : 0,
                'to' => $total > 0 ? min($offset + $perPage, $total) : 0
            ]
        ];
    }
} 