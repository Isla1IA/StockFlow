<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->whenLoaded('product', fn() => $this->product?->name),
            'product_sku' => $this->whenLoaded('product', fn() => $this->product?->sku),
            'quantity' => $this->quantity,
            'unit_price' => (string) $this->unit_price,
            'discount' => (string) $this->discount,
            'tax' => (string) $this->tax,
            'line_total' => (string) $this->line_total,
        ];
    }
}
