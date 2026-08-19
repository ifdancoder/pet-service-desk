<?php

namespace App\Filament\Resources\AuditLogs\Tables;

use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketComment;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AuditLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('auditable_type')
                    ->label('Record')
                    ->formatStateUsing(fn (string $state, $record): string => class_basename($state).' #'.$record->auditable_id),
                TextColumn::make('user.name')
                    ->label('Changed by')
                    ->placeholder('System'),
                TextColumn::make('changes')
                    ->label('What changed')
                    ->formatStateUsing(fn (array $state): array => collect($state)
                        ->map(function ($diff, $field) {
                            if (is_array($diff) && isset($diff['old']) && isset($diff['new'])) {
                                return "{$field}: {$diff['old']} → {$diff['new']}";
                            }
                            return "{$field}: {$diff}";
                        })
                        ->values()
                        ->all())
                    ->listWithLineBreaks(),
                TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime(),
            ])
            ->filters([
                SelectFilter::make('auditable_type')
                    ->label('Record type')
                    ->options([
                        Ticket::class => 'Ticket',
                        TicketComment::class => 'Comment',
                        TicketAttachment::class => 'Attachment',
                        User::class => 'User',
                        SlaPolicy::class => 'SLA Policy',
                    ]),
                SelectFilter::make('user_id')
                    ->label('Changed by')
                    ->relationship('user', 'name'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
