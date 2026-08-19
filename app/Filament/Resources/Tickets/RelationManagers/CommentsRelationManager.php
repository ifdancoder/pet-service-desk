<?php

namespace App\Filament\Resources\Tickets\RelationManagers;

use App\DataTransferObjects\TicketCommentData;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Services\TicketCommentService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('body')
            ->columns([
                TextColumn::make('author.name'),
                TextColumn::make('body')
                    ->limit(80),
                IconColumn::make('is_internal')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->headerActions([
                Action::make('create')
                    ->schema([
                        Textarea::make('body')
                            ->required(),
                        Toggle::make('is_internal'),
                    ])
                    ->visible(fn (): bool => auth()->user()->can('create', [TicketComment::class, $this->getOwnerRecord()]))
                    ->action(function (array $data): void {
                        /** @var Ticket $ticket */
                        $ticket = $this->getOwnerRecord();

                        app(TicketCommentService::class)->create(
                            $ticket,
                            new TicketCommentData(body: $data['body'], isInternal: (bool) ($data['is_internal'] ?? false)),
                            auth()->user(),
                        );
                    }),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->action(fn (TicketComment $record) => app(TicketCommentService::class)->delete($record)),
            ]);
    }
}
