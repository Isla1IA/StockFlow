<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LowStockProductsTableWidget extends TableWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('products.view') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Productos con Bajo Stock')
            ->query(
                Product::query()
                    ->where('is_active', true)
                    ->whereColumn('stock', '<=', 'min_stock')
            )
            ->defaultSort('stock', 'asc')
            ->columns([
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),

                TextColumn::make('name')
                    ->label('Producto')
                    ->searchable(),

                TextColumn::make('stock')
                    ->label('Stock')
                    ->badge()
                    ->color('danger')
                    ->sortable(),

                TextColumn::make('min_stock')
                    ->label('Stock Mínimo')
                    ->sortable(),

                TextColumn::make('stock_gap')
                    ->label('Faltante')
                    ->state(fn(Product $record): int => max($record->min_stock - $record->stock, 0))
                    ->badge()
                    ->color('warning'),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}
