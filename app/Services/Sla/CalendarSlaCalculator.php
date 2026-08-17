<?php

namespace App\Services\Sla;

use App\Models\SlaPolicy;
use App\Models\Ticket;
use Carbon\CarbonImmutable;

class CalendarSlaCalculator implements SlaCalculator
{
    public function calculate(Ticket $ticket): ?CarbonImmutable
    {
        $policy = $this->applicablePolicyFor($ticket);

        if ($policy === null) {
            return null;
        }

        return CarbonImmutable::instance($ticket->created_at)
            ->addMinutes($policy->resolution_time_minutes);
    }

    public function applicablePolicyFor(Ticket $ticket): ?SlaPolicy
    {
        return SlaPolicy::query()
            ->where('active', true)
            ->where('priority', $ticket->priority)
            ->where('category_id', $ticket->category_id)
            ->first()
            ?? SlaPolicy::query()
                ->where('active', true)
                ->where('priority', $ticket->priority)
                ->whereNull('category_id')
                ->first();
    }
}
