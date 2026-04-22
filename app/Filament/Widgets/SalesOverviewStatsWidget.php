<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesOverviewStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Resumen de Ventas';

    protected ?string $pollingInterval = '60s';

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('sales.view') ?? false;
    }

    protected function getStats(): array
    {
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $monthStart = now()->startofMonth();
        $monthEnd = now()->endOfMonth();

        $salesToday = Sale::query()
            ->where('status', Sale::STATUS_CONFIRMED)
            ->whereBetween('sale_date', [$todayStart, $todayEnd])
            ->count();

        $salesMonth = Sale::query()
            ->where('status', Sale::STATUS_CONFIRMED)
            ->whereBetween('sale_date', [$monthStart, $monthEnd])
            ->count();

        $monthlyRevenue = (float) Sale::query()
            ->where('status', Sale::STATUS_CONFIRMED)
            ->whereBetween('sale_date', [$monthStart, $monthEnd])
            ->sum('total');

        return [
            Stat::make('Ventas de hoy', number_format($salesToday))
                ->description('Ventas confirmadas hoy')
                ->icon('heroicon-o-shopping-bag')
                ->color('success'),

            Stat::make('Ventas del mes', number_format($salesMonth))
                ->description('Ventas confirmadas este mes')
                ->icon('heroicon-o-calendar-days')
                ->color('primary'),

            Stat::make('Ingreso mensual', '$' . number_format($monthlyRevenue, 2))
                ->description('Ingreso total este mes')
                ->icon('heroicon-o-banknotes')
                ->color('warning')
        ];
    }
}
