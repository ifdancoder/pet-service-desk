<?php

namespace App\Filament\Resources\Tickets\RelationManagers;

use App\DataTransferObjects\TicketAttachmentData;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Services\TicketAttachmentService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('original_name')
            ->columns([
                TextColumn::make('original_name'),
                TextColumn::make('uploader.name'),
                TextColumn::make('size')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 1024, 1).' KB'),
                TextColumn::make('scanned_at')
                    ->dateTime()
                    ->placeholder('Pending scan'),
            ])
            ->headerActions([
                Action::make('upload')
                    ->schema([
                        // Mirrors StoreTicketAttachmentRequest's API-side rules
                        // ('max:10240' + the same extension allow-list).
                        // Filament's maxSize() is in KB (it compiles straight to
                        // Laravel's "max:" rule) and acceptedFileTypes()
                        // compiles to "mimetypes:", so it takes MIME types.
                        FileUpload::make('file')
                            ->storeFiles(false)
                            ->maxSize(10240)
                            ->acceptedFileTypes([
                                'image/jpeg',
                                'image/png',
                                'image/gif',
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'text/plain',
                                'text/csv',
                            ])
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        /** @var Ticket $ticket */
                        $ticket = $this->getOwnerRecord();

                        app(TicketAttachmentService::class)->store(
                            $ticket,
                            new TicketAttachmentData(file: $data['file']),
                            auth()->user(),
                        );
                    }),
            ])
            ->recordActions([
                Action::make('delete')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->action(fn (TicketAttachment $record) => app(TicketAttachmentService::class)->delete($record)),
            ]);
    }
}
