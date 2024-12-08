<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Pagination extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'total' => $this->total(),
            'data' => $this->items(),
        ];
    }
}
