<?php

namespace App\Filament\Resources\Tickets\Tables;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class TicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subject')
                    ->searchable(),
                TextColumn::make('requester.name'),
                TextColumn::make('assignee.name')
                    ->placeholder('Unassigned'),
                TextColumn::make('department.name'),
                TextColumn::make('team.name')
                    ->placeholder('—'),
                TextColumn::make('category.name'),
                TextColumn::make('priority')
                    ->badge(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('sla_due_at')
                    ->dateTime()
                    ->color(fn (?Carbon $state): ?string => $state?->isPast() ? 'danger' : null)
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(TicketStatus::class),
                SelectFilter::make('priority')
                    ->options(TicketPriority::class),
                SelectFilter::make('department_id')
                    ->relationship('department', 'name'),
                SelectFilter::make('team_id')
                    ->relationship('team', 'name'),
                SelectFilter::make('assignee_id')
                    ->relationship('assignee', 'name'),
                Filter::make('breached')
                    ->query(fn (Builder $query): Builder => $query->whereHas('violations')),
            ]);
    }
}
