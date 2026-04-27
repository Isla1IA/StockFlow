<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sale_number' => $this->sale_number,
            'customer_id' => $this->customer_id,
            'customer_name' => $this->whenLoaded('customer', fn() => $this->customer?->name),
            'created_by' => $this->created_by,
            'creator_name' => $this->whenLoaded('creator', fn() => $this->creator?->name),
            'sale_date' => $this->sale_date?->toIso8601String(),
            'subtotal' => (string) $this->subtotal,
            'discount' => (string) $this->discount,
            'tax' => (string) $this->tax,
            'total' => (string) $this->total,
            'status' => $this->status,
            'notes' => $this->notes,
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancelled_by' => $this->cancelled_by,
            'cancelled_by_name' => $this->whenLoaded('cancelledBy', fn() => $this->cancelledBy?->name),
            'cancellation_reason' => $this->cancellation_reason,
            'details_count' => $this->whenCounted('details'),
            'details' => SaleDetailResource::collection($this->whenLoaded('details')),
            'inventory_movements' => InventoryMovementResource::collection($this->whenLoaded('inventoryMovements')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
