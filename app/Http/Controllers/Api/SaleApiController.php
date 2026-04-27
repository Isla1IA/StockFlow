<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\SaleResource;
use App\Models\Sale;
use App\Services\Sales\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SaleApiController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Sale::class);

        $search = (string) $request->string('search');
        $status = (string) $request->string('status');
        $perPage = max(1, min(100, (int) $request->integer('per_page', 15)));
        $allowedStatuses = [Sale::STATUS_DRAFT, Sale::STATUS_CONFIRMED, Sale::STATUS_CANCELLED];


        $sales = Sale::query()
            ->with(['customer:id,name', 'creator:id,name'])
            ->withCount('details')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('sale_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when(in_array($status, $allowedStatuses, true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($request->filled('customer_id'), function ($query) use ($request) {
                $query->where('customer_id', $request->integer('customer_id'));
            })
            ->when($request->filled('date_from'), function ($query) use ($request) {
                $query->whereDate('sale_date', '>=', (string) $request->date('date_from'));
            })
            ->when($request->filled('date_to'), function ($query) use ($request) {
                $query->whereDate('sale_date', '<=', (string) $request->date('date_to'));
            })
            ->latest('sale_date')
            ->paginate($perPage)
            ->withQueryString();

        return SaleResource::collection($sales);
    }

    public function store(Request $request, SaleService $saleService): JsonResponse
    {
        $this->authorize('create', Sale::class);

        $sale = $saleService->registerSale(
            payload: $request->all(),
            userId: (int) $request->user()->id,
        );

        return response()->json([
            'message' => 'Venta creada exitosamente.',
            'data' => new SaleResource($sale),
        ], 201);
    }

    public function show(Sale $sale): SaleResource
    {
        $this->authorize('view', $sale);

        $sale->load([
            'customer:id,name',
            'creator:id,name',
            'cancelledBy:id,name',
            'details.product:id,name,sku',
            'inventoryMovements.product:id,name,sku',
            'inventoryMovements.user:id,name',
        ]);

        return new SaleResource($sale);
    }

    public function cancel(Request $request, Sale $sale, SaleService $saleService): JsonResponse
    {
        $this->authorize('cancel', $sale);

        $validated = $request->validate([
            'cancellation_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $sale = $saleService->canceledSale(
            saleId: $sale->id,
            userId: (int) $request->user()->id,
            reason: $validated['cancellation_reason'] ?? null,
        );

        return response()->json([
            'message' => 'Venta cancelada exitosamente.',
            'data' => new SaleResource($sale),
        ]);
    }
}
