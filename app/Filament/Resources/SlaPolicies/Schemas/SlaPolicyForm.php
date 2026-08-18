<?php

namespace App\Filament\Resources\SlaPolicies\Schemas;

use App\Enums\TicketPriority;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SlaPolicyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->label('Category (leave empty to apply to all categories)'),
                Select::make('priority')
                    ->options(TicketPriority::class)
                    ->required(),
                TextInput::make('response_time_minutes')
                    ->required()
                    ->numeric()
                    ->minValue(1),
                TextInput::make('resolution_time_minutes')
                    ->required()
                    ->numeric()
                    ->minValue(1),
                Toggle::make('active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
