<?php

namespace App\Jobs;

use App\Services\Search\TicketSearchService;
use Elastic\Elasticsearch\Exception\ElasticsearchException;
use Elastic\Transport\Exception\TransportException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RemoveTicketFromSearch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $ticketId) {}

    public function handle(TicketSearchService $search): void
    {
        try {
            $search->delete($this->ticketId);
        } catch (TransportException|ElasticsearchException $exception) {
            Log::warning('Could not remove ticket from Elasticsearch.', [
                'ticket_id' => $this->ticketId,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
