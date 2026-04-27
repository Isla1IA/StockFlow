<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\InventoryMovementResource;
use App\Models\InventoryMovement;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InventoryMovementApiController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeView($request);

        $search = (string) $request->string('search');
        $perPage = max(1, min(100, (int) $request->integer('per_page', 15)));

        $movements = InventoryMovement::query()
            ->with(['product:id,name,sku', 'user:id,name'])
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('product', function ($productQuery) use ($search) {
                    $productQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('product_id'), function ($query) use ($request) {
                $query->where('product_id', $request->integer('product_id'));
            })
            ->when($request->filled('movement_type'), function ($query) use ($request) {
                $query->where('movement_type', (string) $request->string('movement_type'));
            })
            ->when($request->filled('reason'), function ($query) use ($request) {
                $query->where('reason', (string) $request->string('reason'));
            })
            ->when($request->filled('date_from'), function ($query) use ($request) {
                $query->whereDate('occurred_at', '>=', (string) $request->date('date_from'));
            })
            ->when($request->filled('date_to'), function ($query) use ($request) {
                $query->whereDate('occurred_at', '<=', (string) $request->date('date_to'));
            })
            ->latest('occurred_at')
            ->paginate($perPage)
            ->withQueryString();

        return InventoryMovementResource::collection($movements);
    }

    public function show(Request $request, InventoryMovement $inventoryMovement): InventoryMovementResource
    {
        $this->authorizeView($request);

        return new InventoryMovementResource(
            $inventoryMovement->load(['product:id,name,sku', 'user:id,name'])
        );
    }

    private function authorizeView(Request $request): void
    {
        $canView = $request->user()?->can('products.view') || $request->user()?->can('sales.view');

        if (!$canView) {
            throw new AuthorizationException('No tienes permiso para ver los movimientos de inventario.');
        }
    }
}
