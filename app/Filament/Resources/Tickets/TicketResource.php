<?php

namespace App\Filament\Resources\Tickets;

use App\Enums\TicketPriority;
use App\Filament\Resources\Tickets\Pages\CreateTicket;
use App\Filament\Resources\Tickets\Pages\EditTicket;
use App\Filament\Resources\Tickets\Pages\ListTickets;
use App\Filament\Resources\Tickets\RelationManagers\CommentsRelationManager;
use App\Filament\Resources\Tickets\Schemas\TicketForm;
use App\Filament\Resources\Tickets\Tables\TicketsTable;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TicketForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TicketsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->visibleTo(auth()->user());
    }

    public static function canEdit(Model $record): bool
    {
        /** @var Ticket $record */
        return auth()->user()->can('ticket.manage') && auth()->user()->can('view', $record);
    }

    public static function assignAction(): Action
    {
        return Action::make('assign')
            ->schema([
                Select::make('assignee_id')
                    ->label('Assignee')
                    ->options(fn () => User::query()->pluck('name', 'id'))
                    ->required(),
            ])
            ->visible(fn (Ticket $record): bool => auth()->user()->can('assign', $record))
            ->action(function (Ticket $record, array $data): void {
                $assignee = User::findOrFail($data['assignee_id']);
                app(TicketService::class)->assign($record, $assignee);
            });
    }

    public static function closeAction(): Action
    {
        return Action::make('close')
            ->requiresConfirmation()
            ->visible(fn (Ticket $record): bool => auth()->user()->can('close', $record))
            ->action(fn (Ticket $record) => app(TicketService::class)->close($record));
    }

    public static function reopenAction(): Action
    {
        return Action::make('reopen')
            ->requiresConfirmation()
            ->visible(fn (Ticket $record): bool => auth()->user()->can('reopen', $record))
            ->action(fn (Ticket $record) => app(TicketService::class)->reopen($record));
    }

    public static function changePriorityAction(): Action
    {
        return Action::make('changePriority')
            ->schema([
                Select::make('priority')
                    ->options(TicketPriority::class)
                    ->required(),
            ])
            ->visible(fn (Ticket $record): bool => auth()->user()->can('changePriority', $record))
            ->action(function (Ticket $record, array $data): void {
                $priority = $data['priority'] instanceof TicketPriority
                    ? $data['priority']
                    : TicketPriority::from($data['priority']);

                app(TicketService::class)->changePriority($record, $priority);
            });
    }

    public static function getRelations(): array
    {
        return [
            CommentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTickets::route('/'),
            'create' => CreateTicket::route('/create'),
            'edit' => EditTicket::route('/{record}/edit'),
        ];
    }
}
