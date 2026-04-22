<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use App\Services\Inventory\InventoryService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category.name')
                    ->label('Categoria')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('stock')
                    ->label('Stock')
                    ->numeric()
                    ->badge()
                    ->color(fn(Product $record): string => $record->stock <= $record->min_stock ? 'danger' : 'success')
                    ->sortable(),

                TextColumn::make('min_stock')
                    ->label('Stock minimo')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('price')
                    ->label('Precio')
                    ->money('MXN')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Categoria')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos')
                    ->native(false),

                Filter::make('low_stock')
                    ->label('Stock bajo')
                    ->query(fn(Builder $query): Builder => $query->whereColumn('stock', '<=', 'min_stock')),
            ])
            ->recordActions([
                Action::make('manual_entry')
                    ->label('Entrada')
                    ->icon('heroicon-o-arrow-down-circle')
                    ->color('success')
                    ->modalHeading('Registrar Ajuste Manual')
                    ->modalDescription('Este movimiento incrementa el stock y registra trazabilidad en movimientos de inventario.')
                    ->schema([
                        TextInput::make('quantity')
                            ->label('Cantidad de entrada')
                            ->integer()
                            ->minValue(1)
                            ->required(),

                        DateTimePicker::make('occurred_at')
                            ->label('Fecha y hora de la entrada')
                            ->seconds(false)
                            ->default(now()),

                        Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->authorize(fn(Product $record): bool => auth()->user()?->can('update', $record) ?? false)
                    ->action(function (Product $record, array $data): void {
                        $userId = auth()->id();

                        if (!$userId) {
                            throw ValidationException::withMessages([
                                'user' => 'Usuario Autenticado Requerido'
                            ]);
                        }

                        app(InventoryService::class)->registerManualEntry([
                            'product_id' => $record->id,
                            'quantity' => $data['quantity'],
                            'occurred_at' => $data['occurred_at'] ?? null,
                            'notes' => $data['notes'] ?? null,
                        ], $userId);
                    })
                    ->successNotificationTitle('Entrada Manual Registrada'),

                Action::make('manual_adjustment')
                    ->label('Ajuste')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->color('warning')
                    ->modalHeading('Registrar Ajuste Manual')
                    ->modalDescription('Define el stock final real del producto. El sistema calculará la diferencia.')
                    ->schema([
                        TextInput::make(('new_stock'))
                            ->label('Nuevo Stock')
                            ->integer()
                            ->minValue(0)
                            ->required(),

                        DateTimePicker::make('occurred_at')
                            ->label('Fecha y hora')
                            ->seconds(false)
                            ->default(now()),

                        Textarea::make('notes')
                            ->label('Motivo / Observaciones')
                            ->rows(3)
                            ->required()
                            ->minLength(3)
                            ->maxLength(1000),
                    ])
                    ->authorize(fn(Product $record): bool => auth()->user()?->can('update', $record) ?? false)
                    ->action(function (Product $record, array $data): void {
                        $userId = auth()->id();

                        if (!$userId) {
                            throw ValidationException::withMessages([
                                'user' => 'Usuario Autenticado Requerido'
                            ]);
                        }

                        app(InventoryService::class)->registerManualAdjustment([
                            'product_id' => $record->id,
                            'new_stock' => $data['new_stock'],
                            'occurred_at' => $data['occurred_at'] ?? null,
                            'notes' => $data['notes'],
                        ], $userId);
                    })
                    ->successNotificationTitle('Ajuste Manual Registrado Correctamente'),
                EditAction::make(),
                DeleteAction::make(),

            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }
}
