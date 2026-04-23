<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => $this->category_id,
            'category_name' => $this->whenLoaded('category', fn() => $this->category?->name),
            'name' => $this->name,
            'sku' => $this->sku,
            'stock' => $this->stock,
            'min_stock' => $this->min_stock,
            'price' => (string) $this->price,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
