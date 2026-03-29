<?php

namespace App\Filament\Resources\Sales\Schemas;

use App\Models\Product;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class SaleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Encabezado')
                    ->schema([
                        TextInput::make('sale_number')
                            ->label('Folio')
                            ->maxLength(30)
                            ->helperText('Opcional. Si lo dejas vacio, se generará automáticamente.'),

                        Select::make('customer_id')
                            ->label('Cliente')
                            ->relationship(
                                name: 'customer',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn(Builder $query) => $query->where('is_active', true),
                            )
                            ->searchable()
                            ->preload()
                            ->required(),

                        DateTimePicker::make('sale_date')
                            ->label('Fecha de venta')
                            ->seconds(false)
                            ->default(now())
                            ->required(),

                        Hidden::make('status')
                            ->default('confirmed'),
                    ])
                    ->columns(2),

                Section::make('Detalle')
                    ->schema([
                        Repeater::make('items')
                            ->label('Productos')
                            ->schema([
                                Select::make('product_id')
                                    ->label('Producto')
                                    ->options(fn(): array => Product::query()
                                        ->where('is_active', true)
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set): void {
                                        if (blank($state)) {
                                            return;
                                        }

                                        $price = Product::query()->whereKey($state)->value('price');

                                        if ($price !== null) {
                                            $set('unit_price', (string) $price);
                                        }
                                    }),

                                TextInput::make('quantity')
                                    ->label('Cantidad')
                                    ->integer()
                                    ->minValue(1)
                                    ->default(1)
                                    ->required(),

                                TextInput::make('unit_price')
                                    ->label('Precio unitario')
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('$')
                                    ->helperText('Opcional. Si lo dejas vacio, se usará el precio actual del producto.'),
                            ])
                            ->minItems(1)
                            ->defaultItems(1)
                            ->reorderable(false)
                            ->columns(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Notas')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Notas/Observaciones')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
