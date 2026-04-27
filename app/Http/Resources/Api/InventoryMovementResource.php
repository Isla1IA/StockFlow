<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->whenLoaded('product', fn() => $this->product?->name),
            'product_sku' => $this->whenLoaded('product', fn() => $this->product?->sku),
            'user_id' => $this->user_id,
            'user_name' => $this->whenLoaded('user', fn() => $this->user?->name),
            'movement_type' => $this->movement_type,
            'reason' => $this->reason,
            'quantity' => $this->quantity,
            'stock_before' => $this->stock_before,
            'stock_after' => $this->stock_after,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'occurred_at' => $this->occurred_at->toIso8601String(),
            'notes' => $this->notes,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
