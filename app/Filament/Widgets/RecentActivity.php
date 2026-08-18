<?php

namespace App\Filament\Widgets;

use App\Models\Ticket;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentActivity extends TableWidget
{
    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => Ticket::query()
                    ->visibleTo(auth()->user())
                    ->latest('updated_at'),
            )
            ->columns([
                TextColumn::make('subject'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('assignee.name')
                    ->placeholder('Unassigned'),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->since(),
            ])
            ->paginated([5, 10, 25]);
    }
}
