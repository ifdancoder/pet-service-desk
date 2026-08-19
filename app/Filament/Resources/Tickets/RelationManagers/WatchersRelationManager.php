<?php

namespace App\Filament\Resources\Tickets\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Facades\Filament;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class WatchersRelationManager extends RelationManager
{
    protected static string $relationship = 'watchers';

    /**
     * Authorize against the OWNER ticket, not the related User model.
     *
     * The parent implementation checks the related model's `viewAny` policy
     * (here `UserPolicy::viewAny()`, gated on `user.manage`, administrator
     * only), which would hide the Watchers tab from every other staff role.
     */
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return Filament::auth()->user()->can('view', $ownerRecord);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
            ])
            ->headerActions([
                AttachAction::make(),
            ])
            ->recordActions([
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
