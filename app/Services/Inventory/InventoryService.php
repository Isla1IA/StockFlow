<?php

namespace App\Services\Inventory;

use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function registerManualEntry(array $payload, int $userId): InventoryMovement
    {
        $validated = Validator::make($payload, [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'occurred_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ])->validated();

        return DB::transaction(function () use ($validated, $userId): InventoryMovement {
            $product = Product::query()
                ->whereKey($validated['product_id'])
                ->lockForUpdate()
                ->first();

            if (!$product) {
                throw ValidationException::withMessages([
                    'product_id' => 'El Producto no Existe'
                ]);
            }

            if (!$product->is_active) {
                throw ValidationException::withMessages([
                    'product_id' => 'No se puede registrar una entrada manual para un producto inactivo'
                ]);
            }

            $quantity = (int) $validated['quantity'];
            $before = (int) $product->stock;
            $after = $before + $quantity;

            $product->update([
                'stock' => $after,
            ]);

            $movement = InventoryMovement::query()->create([
                'product_id' => $product->id,
                'user_id' => $userId,
                'movement_type' => InventoryMovement::TYPE_IN,
                'reason' => InventoryMovement::REASON_MANUAL_ENTRY,
                'quantity' => $quantity,
                'stock_before' => $before,
                'stock_after' => $after,
                'occurred_at' => isset($validated['occurred_at']) ? Carbon::parse($validated['occurred_at']) : Carbon::now(),
                'notes' => $validated['notes'] ?? null,
            ]);

            return $movement->load(['product', 'user']);
        }, 3);
    }

    public function registerManualAdjustment(array $payload, int $userId): InventoryMovement
    {
        $validated = Validator::make($payload, [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'new_stock' => ['required', 'integer', 'min:0'],
            'occurred_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'min:3', 'max:1000'],
        ])->validated();

        return DB::transaction(function () use ($validated, $userId): InventoryMovement {
            $product = Product::query()
                ->whereKey($validated['product_id'])
                ->lockForUpdate()
                ->first();

            if (!$product) {
                throw ValidationException::withMessages([
                    'product_id' => 'El Producto no Existe'
                ]);
            }

            if (!$product->is_active) {
                throw ValidationException::withMessages([
                    'product_id' => 'No se puede registrar una entrada manual para un producto inactivo'
                ]);
            }

            $before = (int) $product->stock;
            $after = (int) $validated['new_stock'];

            if ($before === $after) {
                throw ValidationException::withMessages([
                    'new_stock' => 'El nuevo stock debe ser diferente al stock actual'
                ]);
            }

            $delta = $after - $before;
            $movementType = $delta > 0 ? InventoryMovement::TYPE_IN : InventoryMovement::TYPE_OUT;

            $quantity = abs($delta);

            $product->update([
                'stock' => $after,
            ]);

            $movement = InventoryMovement::query()->create([
                'product_id' => $product->id,
                'user_id' => $userId,
                'movement_type' => $movementType,
                'reason' => InventoryMovement::REASON_MANUAL_ADJUSTMENT,
                'quantity' => $quantity,
                'stock_before' => $before,
                'stock_after' => $after,
                'occurred_at' => isset($validated['occurred_at']) ? Carbon::parse($validated['occurred_at']) : Carbon::now(),
                'notes' => $validated['notes'] ?? null,
            ]);

            return $movement->load(['product', 'user']);
        }, 3);
    }
}
