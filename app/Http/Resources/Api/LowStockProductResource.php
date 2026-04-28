<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LowStockProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $stock = (int) $this->stock;
        $minStock = (int) $this->min_stock;

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'stock' => $stock,
            'min_stock' => $minStock,
            'stock_gap' => max($minStock - $stock, 0)
        ];
    }
}
