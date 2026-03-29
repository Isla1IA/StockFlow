<?php

namespace App\Filament\Resources\Sales\Schemas;


use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;


class SaleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Resumen de la venta')
                    ->schema([
                        TextEntry::make('sale_number')
                            ->label('Folio'),

                        TextEntry::make('customer.name')
                            ->label('Cliente'),

                        TextEntry::make('creator.name')
                            ->label('Creada por'),

                        TextEntry::make('sale_date')
                            ->label('Fecha')
                            ->dateTime('d/m/Y H:i'),

                        TextEntry::make('total')
                            ->label('Total')
                            ->money('MXN'),

                        TextEntry::make('status')
                            ->label('Estatus')
                            ->badge(),
                    ])
                    ->columns(3),

                Section::make('Detalle de Productos')
                    ->schema([
                        RepeatableEntry::make('details')
                            ->label('Partidas')
                            ->schema([
                                TextEntry::make('product.name')
                                    ->label('Producto'),

                                TextEntry::make('quantity')
                                    ->label('Cantidad')
                                    ->numeric(),

                                TextEntry::make('unit_price')
                                    ->label('Precio unitario')
                                    ->money('MXN'),

                                TextEntry::make('line_total')
                                    ->label('Precio total')
                                    ->money('MXN'),
                            ])
                            ->columns(4)
                            ->columnSpanFull(),
                    ]),
                Section::make('Observaciones')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Notas')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
