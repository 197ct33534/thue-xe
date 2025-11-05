<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class BookingCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => BookingResource::collection($this->collection),
            'meta' => [
                'current_page' => $this->currentPage(),
                'total' => $this->total(),
                'per_page' => $this->perPage(),
            ],
        ];
    }
}