<?php

namespace App\Services\Inventory;

use App\Models\LowStockAlert;
use App\Models\Product;
use Illuminate\Support\Collection;

class LowStockAlertService
{
    public function detectLowStockProducts(): Collection
    {
        return Product::query()
            ->where('is_active', true)
            ->whereColumn('stock', '<=', 'min_stock')
            ->orderBy('stock')
            ->get();
    }

    /**
     * @return array{0: LowStockAlert, 1: bool} [alert, shouldNotify]
     */

    public function activateAlert(Product $product): array
    {
        $alert = LowStockAlert::query()->firstWhere('product_id', $product->id);

        if (!$alert) {
            $alert = LowStockAlert::query()->create([
                'product_id' => $product->id,
                'stock' => (int) $product->stock,
                'min_stock' => (int) $product->min_stock,
                'is_active' => true,
                'last_detected_at' => now(),
                'resolved_at' => null,
            ]);
            return [$alert->load('product'), true];
        }

        $wasActive = (bool) $alert->is_active;

        $alert->update([
            'stock' => (int) $product->stock,
            'min_stock' => (int) $product->min_stock,
            'is_active' => true,
            'last_detected_at' => now(),
            'resolved_at' => null,
        ]);

        return [$alert->fresh('product'), ! $wasActive];
    }

    public function resolveRecoveredAlerts(array $currentLowStockProductIds): int
    {
        return LowStockAlert::query()
            ->where('is_active', true)
            ->when(
                !empty($currentLowStockProductIds),
                fn($query) => $query->whereNotIn('product_id', $currentLowStockProductIds),
                fn($query) => $query
            )
            ->update([
                'is_active' => false,
                'resolved_at' => now(),
            ]);
    }
}
