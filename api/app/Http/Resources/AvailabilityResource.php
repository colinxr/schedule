<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AvailabilityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'available_slots' => $this->resource['data'],
            'pagination' => [
                'total' => $this->resource['pagination']['total'],
                'total_pages' => $this->resource['pagination']['total_pages'],
                'has_more_pages' => $this->resource['pagination']['has_more_pages'],
                'current_page' => $this->resource['pagination']['current_page'],
                'per_page' => $this->resource['pagination']['per_page'],
                'from' => $this->resource['pagination']['from'],
                'to' => $this->resource['pagination']['to']
            ]
        ];
    }
} 