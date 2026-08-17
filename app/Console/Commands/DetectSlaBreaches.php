<?php

namespace App\Console\Commands;

use App\Events\SlaBreached;
use App\Models\Ticket;
use App\Services\Sla\CalendarSlaCalculator;
use Illuminate\Console\Command;

class DetectSlaBreaches extends Command
{
    protected $signature = 'tickets:detect-sla-breaches';

    protected $description = 'Detect tickets that have passed their SLA due date and record a violation for each.';

    public function handle(CalendarSlaCalculator $calculator): int
    {
        Ticket::query()
            ->whereNotNull('sla_due_at')
            ->where('sla_due_at', '<', now())
            ->open()
            ->whereDoesntHave('violations')
            ->each(function (Ticket $ticket) use ($calculator) {
                $policy = $calculator->applicablePolicyFor($ticket);

                $violation = $ticket->violations()->create([
                    'sla_policy_id' => $policy?->id,
                    'breached_at' => now(),
                ]);

                event(new SlaBreached($violation));
            });

        return self::SUCCESS;
    }
}
