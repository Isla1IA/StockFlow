<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_code' => $this->customer_code,
            'name' => $this->name,
            'tax_id' => $this->tax_id,
            'email' => $this->email,
            'phone' => $this->phone,
            'city' => $this->city,
            'state' => $this->state,
            'address' => $this->address,
            'credit_limit' => (string) $this->credit_limit,
            'is_active' => $this->is_active,
            'created_by' => $this->created_by,
            'creator_name' => $this->whenLoaded('creator', fn() => $this->creator?->name),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
