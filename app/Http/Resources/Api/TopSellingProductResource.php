<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TopSellingProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'product_id' => (int) $this->product_id,
            'product_name' => $this->whenLoaded('product', fn() => $this->product?->name),
            'product_sku' => $this->whenLoaded('product', fn() => $this->product?->sku),
            'total_quantity' => (int) $this->total_quantity,
            'total_amount' => number_format((float) $this->total_amount, 2, '.', ''),
        ];
    }
}
