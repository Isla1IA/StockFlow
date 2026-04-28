<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\LowStockProductResource;
use App\Http\Resources\Api\TopSellingProductResource;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

class SummaryApiController extends Controller
{
    public function salesToday(Request $request): JsonResponse
    {
        $this->ensurePermission($request, 'sales.view');

        $start = now()->startOfDay();
        $end = now()->endOfDay();

        $salesCount = Sale::query()
            ->where('status', Sale::STATUS_CONFIRMED)
            ->whereBetween('sale_date', [$start, $end])
            ->count();

        $revenue = (float) Sale::query()
            ->where('status', Sale::STATUS_CONFIRMED)
            ->whereBetween('sale_date', [$start, $end])
            ->sum('total');

        return response()->json([
            'data' => [
                'date' => now()->toDateString(),
                'sales_count' => $salesCount,
                'revenue' => number_format($revenue, 2, '.', ''),
            ]
        ]);
    }

    public function salesMonth(Request $request): JsonResponse
    {
        $this->ensurePermission($request, 'sales.view');

        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
        ]);

        $year = (int) ($validated['year'] ?? now()->year);
        $month = (int) ($validated['month'] ?? now()->month);

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = Carbon::create($year, $month, 1)->endOfMonth();

        $salesCount = Sale::query()
            ->where('status', Sale::STATUS_CONFIRMED)
            ->whereBetween('sale_date', [$start, $end])
            ->count();

        $revenue = (float) Sale::query()
            ->where('status', Sale::STATUS_CONFIRMED)
            ->whereBetween('sale_date', [$start, $end])
            ->sum('total');

        return response()->json([
            'data' => [
                'year' => $year,
                'month' => $month,
                'sales_count' => $salesCount,
                'revenue' => number_format($revenue, 2, '.', ''),
            ]
        ]);
    }

    public function lowStockProducts(Request $request): AnonymousResourceCollection
    {
        $this->ensurePermission($request, 'products.view');

        $perPage = max(1, (int) $request->query('per_page', 15));

        $products = Product::query()
            ->where('is_active', true)
            ->whereColumn('stock', '<=', 'min_stock')
            ->orderBy('stock')
            ->paginate($perPage)
            ->withQueryString();

        return LowStockProductResource::collection($products);
    }

    public function topSellingProducts(Request $request): AnonymousResourceCollection
    {
        $this->ensurePermission($request, 'sales.view');

        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $year = (int) ($validated['year'] ?? now()->year);
        $month = (int) ($validated['month'] ?? now()->month);
        $limit = (int) ($validated['limit'] ?? 10);

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = Carbon::create($year, $month, 1)->endOfMonth();

        $rows = SaleDetail::query()
            ->selectRaw('product_id, SUM(quantity) as total_quantity, SUM(line_total) as total_amount')
            ->whereHas('sale', function (Builder $query) use ($start, $end): void {
                $query->where('status', Sale::STATUS_CONFIRMED)
                    ->whereBetween('sale_date', [$start, $end]);
            })
            ->with('product:id,name,sku')
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get();

        return TopSellingProductResource::collection($rows)->additional([
            'meta' => [
                'year' => $year,
                'month' => $month,
                'limit' => $limit,
            ],
        ]);
    }

    public function monthlyRevenue(Request $request): JsonResponse
    {
        $this->ensurePermission($request, 'sales.view');

        $months = max(1, min(24, (int) $request->integer('months', 12)));

        $endMonth = now()->startOfMonth();
        $startMonth = (clone $endMonth)->subMonth($months - 1);

        $rows = Sale::query()
            ->selectRaw('YEAR(sale_date) as year, MONTH(sale_date) as month, COUNT(*) as sales_count, SUM(total) as revenue')
            ->where('status', Sale::STATUS_CONFIRMED)
            ->whereBetween('sale_date', [$startMonth, (clone $endMonth)->endOfMonth()])
            ->groupByRaw('YEAR(sale_date), MONTH(sale_date)')
            ->orderByRaw('YEAR(sale_date), MONTH(sale_date)')
            ->get()
            ->keyBy(fn($row) => sprintf('%04d-%02d', $row->year, $row->month));

        $data = [];
        $cursor = $startMonth->copy();

        while ($cursor->lte($endMonth)) {
            $key = $cursor->format('Y-m');
            $row = $rows->get($key);

            $data[] = [
                'month' => $key,
                'sales_count' => (int) ($row->sales_count ?? 0),
                'revenue' => number_format((float) ($row->revenue ?? 0), 2, '.', ''),
            ];

            $cursor->addMonth();
        }

        return response()->json([
            'data' => $data,
            'meta' => [
                'months' => $months,
            ],
        ]);
    }

    private function ensurePermission(Request $request, string $permission): void
    {
        abort_unless(
            $request->user()?->can($permission),
            403,
            'No tienes permisos para consultar este resumen.'
        );
    }
}
