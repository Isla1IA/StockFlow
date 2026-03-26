<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos generales')
                    ->schema([
                        Select::make('category_id')
                            ->label('Categoria')
                            ->relationship(name: 'category', titleAttribute: 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(150),
                        TextInput::make('sku')
                            ->label('SKU')
                            ->required()
                            ->maxLength(64)
                            ->unique(ignoreRecord: true),
                        Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true)
                            ->inline(false),
                    ])
                    ->columns(2),
                Section::make('Inventario y precio')
                    ->schema([
                        TextInput::make('stock')
                            ->label('Stock actual')
                            ->required()
                            ->integer()
                            ->minValue(0)
                            ->default(0),
                        TextInput::make('min_stock')
                            ->label('Stock minimo')
                            ->required()
                            ->integer()
                            ->minValue(0)
                            ->default(0),
                        TextInput::make('price')
                            ->label('Precio')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefix('$'),
                    ])
                    ->columns(3),
                Section::make('Descripcion')
                    ->schema([
                        Textarea::make('description')
                            ->label('Descripcion')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
