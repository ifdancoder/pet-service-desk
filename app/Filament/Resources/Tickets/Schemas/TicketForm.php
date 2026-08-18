<?php

namespace App\Filament\Resources\Tickets\Schemas;

use App\Enums\TicketPriority;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('requester_id')
                    ->relationship('requester', 'name')
                    ->required()
                    ->visibleOn('create'),
                TextInput::make('subject')
                    ->required(),
                Textarea::make('description')
                    ->required(),
                Select::make('priority')
                    ->options(TicketPriority::class)
                    ->required()
                    ->visibleOn('create'),
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->required(),
                Select::make('department_id')
                    ->relationship('department', 'name')
                    ->required(),
                Select::make('team_id')
                    ->relationship('team', 'name'),
            ]);
    }
}
