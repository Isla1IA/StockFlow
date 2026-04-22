<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use App\Models\SaleDetail;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;


class TopSellingProductsChartWidget extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Productos Más Vendidos (Mes Actual)';

    protected ?string $pollingInterval = '120s';

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('sales.view') ?? false;
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $topProducts = SaleDetail::query()
            ->selectRaw('product_id, SUM(quantity) as total_quantity')
            ->whereHas('sale', function (Builder $query) use ($monthStart, $monthEnd): void {
                $query->where('status', Sale::STATUS_CONFIRMED)
                    ->whereBetween('sale_date', [$monthStart, $monthEnd]);
            })
            ->with('product:id,name')
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get();

        $labels = $topProducts
            ->map(fn($row) => $row->product?->name ?? ('Producto #' . $row->product_id))
            ->all();

        $values = $topProducts
            ->map(fn($row) => (int) $row->total_quantity)
            ->all();

        return [
            'datasets' => [
                [
                    'label' => 'Unidades Vendidas',
                    'data' => $values,
                    'backgroundColor' => '#02D17E',
                ],
            ],
            'labels' => $labels,
        ];
    }
}
