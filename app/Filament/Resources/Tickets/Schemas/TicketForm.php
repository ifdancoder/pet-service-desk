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
                // Reassigning a ticket's team is gated on 'ticket.assign' on the
                // API side (UpdateTicketRequest's ProhibitedWithoutPermission).
                // disabled() already implies saved(false) (see the schemas
                // package's CanBeDisabled trait), which HasState::isDehydrated()
                // falls back to. But Select::relationship() installs its own
                // dehydrated() closure, so the gate is restated explicitly here
                // instead of resting on call order. Either way the key ends up
                // absent from $data for non-holders, which EditTicket handles by
                // preserving the record's current team.
                Select::make('team_id')
                    ->relationship('team', 'name')
                    ->disabled(fn (): bool => ! auth()->user()->can('ticket.assign'))
                    ->dehydrated(fn (): bool => auth()->user()->can('ticket.assign')),
            ]);
    }
}
