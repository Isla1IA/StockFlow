<?php

namespace App\Filament\Resources\Sales\Tables;

use App\Filament\Resources\Sales\SaleResource;
use App\Models\Sale;
use App\Services\Sales\SaleService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;


class SalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sale_number')
                    ->label('Folio')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('creator.name')
                    ->label('Creada por')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('MXN')
                    ->sortable(),

                TextColumn::make('sale_date')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Estatus')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        Sale::STATUS_CONFIRMED => 'success',
                        Sale::STATUS_DRAFT => 'warning',
                        Sale::STATUS_CANCELLED => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        Sale::STATUS_DRAFT => 'Borrador',
                        Sale::STATUS_CONFIRMED => 'Confirmada',
                        Sale::STATUS_CANCELLED => 'Cancelada',
                    ]),
            ])
            ->recordActions([
                Action::make('cancel')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Cancelar Venta')
                    ->modalDescription('Se devolverá el stock de los productos y se registrará el movimiento de inventario')
                    ->schema([
                        Textarea::make('cancellation_reason')
                            ->label('Motivo de cancelación')
                            ->rows(3)
                            ->maxLength(500),
                    ])
                    ->visible(fn(Sale $record): bool => $record->status !== Sale::STATUS_CANCELLED)
                    ->action(function (Sale $record, array $data): void {
                        $userId = auth()->id();

                        if (! $userId) {
                            throw ValidationException::withMessages([
                                'user' => 'Authenticated user is required.',
                            ]);
                        }
                        app(SaleService::class)->canceledSale(
                            saleId: $record->id,
                            userId: $userId,
                            reason: $data['cancellation_reason'] ?? null,
                        );
                    })
                    ->successNotificationTitle('Venta Cancelada Correctamente')
                    ->authorize(fn(Sale $record): bool => auth()->user()?->can('cancel', $record) ?? false),

                ViewAction::make()
            ])
            ->recordUrl(fn(Sale $record): string => SaleResource::getUrl('view', ['record' => $record]))
            ->defaultSort('sale_date', 'desc');
    }
}
