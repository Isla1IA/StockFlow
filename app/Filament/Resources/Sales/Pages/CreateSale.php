<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Filament\Resources\Sales\SaleResource;
use App\Services\Sales\SaleService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        $userId = auth()->id();

        if (! $userId) {
            throw ValidationException::withMessages([
                'user' => 'Authenticated user is required.',
            ]);
        }

        return app(SaleService::class)->registerSale($data, $userId);
    }
}
