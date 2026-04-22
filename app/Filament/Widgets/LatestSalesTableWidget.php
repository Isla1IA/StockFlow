<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Sales\SaleResource;
use App\Models\Sale;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestSalesTableWidget extends TableWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('sales.view') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Últimas Ventas')
            ->query(Sale::query()->with(['customer:id,name', 'creator:id,name']))
            ->defaultSort('sale_date', 'desc')
            ->columns([
                TextColumn::make('sale_number')
                    ->label('Folio')
                    ->url(fn(Sale $record): string => SaleResource::getUrl('view', ['record' => $record]))
                    ->searchable(),

                TextColumn::make('customer.name')
                    ->label('Cliente')
                    ->searchable(),

                TextColumn::make('creator.name')
                    ->label('Usuario')
                    ->searchable(),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('MXN')
                    ->sortable(),

                TextColumn::make('sale_date')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        Sale::STATUS_CONFIRMED => 'success',
                        Sale::STATUS_DRAFT => 'warning',
                        Sale::STATUS_CANCELLED => 'danger',
                        default => 'gray',
                    }),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}
