<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Filament\Resources\Sales\SaleResource;
use App\Models\Sale;
use App\Services\Sales\SaleService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Validation\ValidationException;


class ViewSale extends ViewRecord
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cancel')
                ->label('Cancelar Venta')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Cancelar Venta')
                ->modalDescription('Se devolverá el stock y se registrará el movimiento de inventario.')
                ->schema([
                    Textarea::make('cancellation_reason')
                        ->label('Motivo de cancelación')
                        ->rows(3)
                        ->maxLength(500),
                ])
                ->visible(fn(): bool => $this->record->status !== Sale::STATUS_CANCELLED)
                ->action(function (array $data): void {
                    $userId = auth()->id();

                    if (! $userId) {
                        throw ValidationException::withMessages([
                            'user' => 'Authenticated user is required.',
                        ]);
                    }

                    app(SaleService::class)->canceledSale(
                        saleId: $this->record->id,
                        userId: $userId,
                        reason: $data['cancellation_reason'] ?? null,
                    );

                    $this->record->refresh();
                })
                ->successNotificationTitle('Venta Cancelada Correctamente'),
        ];
    }
}
