<?php

namespace App\Filament\Widgets;

use App\Models\Ticket;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class SlaBreachTracker extends TableWidget
{
    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => Ticket::query()
                    ->visibleTo(auth()->user())
                    // Matches DetectSlaBreaches' own ->open() scoping: a closed
                    // ticket is no longer actionable and must not linger here.
                    ->open()
                    ->whereNotNull('sla_due_at')
                    ->where(function (Builder $query): void {
                        $query->whereHas('violations')
                            ->orWhere('sla_due_at', '<=', Carbon::now()->addHours(2));
                    })
                    ->orderBy('sla_due_at'),
            )
            ->columns([
                TextColumn::make('subject'),
                TextColumn::make('assignee.name')
                    ->placeholder('Unassigned'),
                TextColumn::make('sla_due_at')
                    ->dateTime()
                    ->color(fn (?Carbon $state): ?string => $state?->isPast() ? 'danger' : 'warning'),
            ]);
    }
}
