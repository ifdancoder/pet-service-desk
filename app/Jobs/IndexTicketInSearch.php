<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Services\Search\TicketSearchService;
use Elastic\Elasticsearch\Exception\ElasticsearchException;
use Elastic\Transport\Exception\TransportException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class IndexTicketInSearch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $deleteWhenMissingModels = true;

    public function __construct(public readonly Ticket $ticket) {}

    public function handle(TicketSearchService $search): void
    {
        // Elasticsearch being unreachable should never take the ticket flow
        // down with it. Worst case the ticket stays unindexed until the next
        // save, or a future reindex command picks it up.
        try {
            $search->index($this->ticket);
        } catch (TransportException|ElasticsearchException $exception) {
            Log::warning('Could not index ticket in Elasticsearch.', [
                'ticket_id' => $this->ticket->id,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
