<?php

namespace App\Filament\Resources\Tickets\Tables;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Filament\Resources\Tickets\TicketResource;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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
            ])
            ->recordActions([
                TicketResource::assignAction(),
                TicketResource::closeAction(),
                TicketResource::reopenAction(),
                TicketResource::changePriorityAction(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('bulkAssign')
                        ->schema([
                            Select::make('assignee_id')
                                ->label('Assignee')
                                ->options(fn () => User::query()->pluck('name', 'id'))
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $assignee = User::findOrFail($data['assignee_id']);
                            $records->each(fn (Ticket $ticket) => app(TicketService::class)->assign($ticket, $assignee));
                        }),
                    BulkAction::make('bulkClose')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $records->each(fn (Ticket $ticket) => app(TicketService::class)->close($ticket));
                        }),
                    BulkAction::make('bulkChangePriority')
                        ->schema([
                            Select::make('priority')
                                ->options(TicketPriority::class)
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $priority = $data['priority'] instanceof TicketPriority
                                ? $data['priority']
                                : TicketPriority::from($data['priority']);
                            $records->each(fn (Ticket $ticket) => app(TicketService::class)->changePriority($ticket, $priority));
                        }),
                ]),
            ]);
    }
}
