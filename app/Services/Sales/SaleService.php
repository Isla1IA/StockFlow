<?php

namespace App\Services\Sales;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\InventoryMovement;



class SaleService
{
    /**
     * Expected payload:
     * [
     *   'customer_id' => int,
     *   'sale_date' => 'Y-m-d H:i:s' | null,
     *   'sale_number' => string | null,
     *   'status' => string | null,
     *   'notes' => string | null,
     *   'items' => [
     *      [
     *        'product_id' => int,
     *        'quantity' => int,
     *        'unit_price' => float|null,
     *        'discount' => float|null,
     *        'tax' => float|null,
     *      ],
     *   ],
     * ]
     */

    public function registerSale(array $payload, int $userId): Sale
    {
        $validated = $this->validatePayload($payload);

        return DB::transaction(function () use ($validated, $userId): Sale {
            $customer = Customer::query()
                ->whereKey($validated['customer_id'])
                ->where('is_active', true)
                ->first();
            if (! $customer) {
                throw ValidationException::withMessages([
                    'customer_id' => 'The selected customer is invalid or inactive.',
                ]);
            }

            $items = $validated['items'];
            $groupedQuantities = $this->groupQuantitiesByProduct($items);
            $productsIds = array_keys($groupedQuantities);

            $products = Product::query()
                ->whereIn('id', $productsIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== count($productsIds)) {
                throw ValidationException::withMessages([
                    'items' => 'One or more products do not exist.',
                ]);
            }

            foreach ($groupedQuantities as $productId => $requestedQuantity) {
                $product = $products->get($productId);

                if (! $product->is_active) {
                    throw ValidationException::withMessages([
                        'items' => "Product {$product->name} is inactive.",
                    ]);
                }

                if ($requestedQuantity > (int) $product->stock) {
                    throw ValidationException::withMessages([
                        'items' => "Insufficient stock for product {$product->name}. Available: {$product->stock}, requested: {$requestedQuantity}.",
                    ]);
                }
            }

            $saleDate = isset($validated['sale_date'])
                ? Carbon::parse($validated['sale_date'])
                : now();
            $saleNumber = Arr::get($validated, 'sale_number') ?: $this->generateSaleNumber();
            $status = Arr::get($validated, 'status', Sale::STATUS_CONFIRMED);

            $subtotal = 0.0;
            $discount = 0.0;
            $tax = 0.0;
            $total = 0.0;

            $detailsToCreate = [];
            $movementsToCreate = [];
            $runningStock = [];

            foreach ($items as $item) {
                $productId = (int) $item['product_id'];
                $quantity = (int) $item['quantity'];
                $product = $products->get($productId);

                $unitPrice = isset($item['unit_price'])
                    ? (float) $item['unit_price']
                    : (float) $product->price;

                $lineSubtotal = round($quantity * $unitPrice, 2);
                $lineDiscount = round((float) Arr::get($item, 'discount', 0), 2);
                $lineTax = round((float) Arr::get($item, 'tax', 0), 2);

                if ($lineDiscount > $lineSubtotal) {
                    throw ValidationException::withMessages([
                        'items' => "Discount cannot exceed subtotal for product {$product->name}.",
                    ]);
                }

                $lineaTotal = round($lineSubtotal - $lineDiscount + $lineTax, 2);

                $subtotal += $lineSubtotal;
                $discount += $lineDiscount;
                $tax += $lineTax;
                $total += $lineaTotal;

                $before = $runningStock[$productId] ?? (int) $product->stock;
                $after = $before - $quantity;
                $runningStock[$productId] = $after;

                $detailsToCreate[] = [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount' => $lineDiscount,
                    'tax' => $lineTax,
                    'line_total' => $lineaTotal,
                ];

                //Nota: quantity esta guardadado como positivo por en el esquema es unsigned
                //movement_type/reason indica que es una salida por venta

                $movementsToCreate[] = [
                    'product_id' => $productId,
                    'user_id' => $userId,
                    'movement_type' => 'out',
                    'reason' => 'sale',
                    'quantity' => $quantity,
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'occurred_at' => $saleDate,
                    'notes' => Arr::get($validated, 'notes'),
                ];
            }

            $sale = Sale::query()->create([
                'sale_number' => $saleNumber,
                'customer_id' => $validated['customer_id'],
                'sale_date' => $saleDate,
                'subtotal' => round($subtotal, 2),
                'discount' => round($discount, 2),
                'tax' => round($tax, 2),
                'total' => round($total, 2),
                'status' => $status,
                'notes' => Arr::get($validated, 'notes'),
                'created_by' => $userId,
            ]);

            $sale->details()->createMany($detailsToCreate);

            foreach ($runningStock as $productId => $stockAfter) {
                $product = $products->get($productId);
                $product->stock = $stockAfter;
                $product->save();
            }

            foreach ($movementsToCreate as $movementData) {
                $sale->inventoryMovements()->create($movementData);
            }

            return $sale->load(['customer', 'details.product', 'creator', 'inventoryMovements']);
        }, 3);
    }

    protected function validatePayload(array $payload): array
    {
        return Validator::make($payload, [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'sale_date' => ['nullable', 'date'],
            'sale_number' => ['nullable', 'string', 'max:30', 'unique:sales,sale_number'],
            'status' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax' => ['nullable', 'numeric', 'min:0'],
        ])->validated();
    }

    protected function groupQuantitiesByProduct(array $items): array
    {
        $grouped = [];

        foreach ($items as $item) {
            $productId = (int) $item['product_id'];
            $quantity = (int) $item['quantity'];

            $grouped[$productId] = ($grouped[$productId] ?? 0) + $quantity;
        }
        return $grouped;
    }

    protected function generateSaleNumber(): string
    {
        do {
            $number = 'VTA-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));
        } while (Sale::query()->where('sale_number', $number)->exists());

        return $number;
    }

    public function canceledSale(int $saleId, int $userId, ?string $reason = null): Sale
    {
        return DB::transaction(function () use ($saleId, $userId, $reason): Sale {
            $sale = Sale::query()
                ->with('details:id,sale_id,product_id,quantity')
                ->lockForUpdate()
                ->findOrFail($saleId);

            if ($sale->status === Sale::STATUS_CANCELLED) {
                throw ValidationException::withMessages([
                    'sale' => 'The sale is already cancelled.',
                ]);
            }

            $groupedQuantities = $sale->details
                ->groupBy('product_id')
                ->map(fn($details) => (int) $details->sum('quantity'))
                ->all();

            if (empty($groupedQuantities)) {
                throw ValidationException::withMessages([
                    'sale' => 'The sale has no items to cancel.',
                ]);
            }

            $productIds = array_keys($groupedQuantities);

            $products = Product::query()
                ->whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== count($productIds)) {
                throw ValidationException::withMessages([
                    'sale' => 'One or more products in the sale not charged exist.',
                ]);
            }

            $occurrredAt = now();
            $cleanReason = blank($reason) ? null : trim($reason);

            foreach ($groupedQuantities as $productId => $quantityToReturn) {
                $product = $products->get($productId);

                $before = (int) $product->stock;
                $after = $before + (int) $quantityToReturn;

                $product->update([
                    'stock' => $after,
                ]);

                $sale->inventoryMovements()->create([
                    'product_id' => $productId,
                    'user_id' => $userId,
                    'movement_type' => InventoryMovement::TYPE_IN,
                    'reason' => InventoryMovement::REASON_CANCEL_SALE,
                    'quantity' => (int) $quantityToReturn, //unsigned siempre positivo
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'occurred_at' => $occurrredAt,
                    'notes' => $cleanReason,
                ]);
            }

            $sale->update([
                'status' => Sale::STATUS_CANCELLED,
                'cancelled_at' => $occurrredAt,
                'cancelled_by' => $userId,
                'cancellation_reason' => $cleanReason,
            ]);

            return $sale->fresh(['customer', 'details.product', 'creator', 'cancelledBy', 'inventoryMovements']);
        }, 3);
    }
}
