<?php

namespace App\Filament\Widgets;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TicketStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $visible = Ticket::query()->visibleTo(auth()->user());

        return [
            Stat::make('Open', (clone $visible)->where('status', TicketStatus::Open)->count()),
            Stat::make('In Progress', (clone $visible)->where('status', TicketStatus::InProgress)->count()),
            Stat::make('On Hold', (clone $visible)->where('status', TicketStatus::OnHold)->count()),
            Stat::make('Low priority', (clone $visible)->where('priority', TicketPriority::Low)->count()),
            Stat::make('Normal priority', (clone $visible)->where('priority', TicketPriority::Normal)->count()),
            Stat::make('High priority', (clone $visible)->where('priority', TicketPriority::High)->count()),
            Stat::make('Critical priority', (clone $visible)->where('priority', TicketPriority::Critical)->count()),
        ];
    }
}
