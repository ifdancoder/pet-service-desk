<?php

namespace App\Console\Commands;

use App\Jobs\IndexTicketInSearch;
use App\Models\Ticket;
use Illuminate\Console\Command;

class ReindexTicketsInSearch extends Command
{
    protected $signature = 'tickets:reindex-search';

    protected $description = 'Queue every ticket for indexing in Elasticsearch. Run this after seeding, or after Elasticsearch has been unreachable for a while.';

    public function handle(): int
    {
        $count = 0;

        Ticket::query()->eachById(function (Ticket $ticket) use (&$count) {
            IndexTicketInSearch::dispatch($ticket);
            $count++;
        });

        $this->info("Queued {$count} tickets for reindexing.");

        return self::SUCCESS;
    }
}
