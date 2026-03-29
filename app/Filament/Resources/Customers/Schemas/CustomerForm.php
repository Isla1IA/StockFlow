<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos Generales')
                    ->schema([
                        TextInput::make('customer_code')
                            ->label('Código de Cliente')
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true),

                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(120),

                        TextInput::make('tax_id')
                            ->label('RFC / Tax ID')
                            ->maxLength(30)
                            ->unique(ignoreRecord: true),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(120)
                            ->unique(ignoreRecord: true),

                        TextInput::make('phone')
                            ->label('Telefono')
                            ->tel()
                            ->maxLength(10),

                        Toggle::make('is_active')
                            ->label('Activo')
                            ->inline(false)
                            ->default(true),
                    ])
                    ->columns(2),
                Section::make('Ubicacion y Credito')
                    ->schema([
                        TextInput::make('city')
                            ->label('Ciudad')
                            ->maxLength(80),

                        TextInput::make('state')
                            ->label('Estado')
                            ->maxLength(80),

                        TextInput::make('credit_limit')
                            ->label('Limite de Credito')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->minValue(0)
                            ->step(0.1),

                        Textarea::make('address')
                            ->label('Direccion')
                            ->rows(3)
                            ->columnSpanFull(),

                    ])
                    ->columns(3),
            ]);
    }
}
