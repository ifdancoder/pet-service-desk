<?php

namespace App\Services\Search;

use App\Models\Ticket;
use Elastic\Elasticsearch\Client;

class ElasticsearchTicketSearchClient implements TicketSearchClient
{
    public function __construct(private readonly Client $client) {}

    public function index(Ticket $ticket): void
    {
        $this->client->index([
            'index' => config('elasticsearch.ticket_index'),
            'id' => (string) $ticket->id,
            'body' => [
                'subject' => $ticket->subject,
                'description' => $ticket->description,
            ],
        ]);
    }

    public function delete(int $ticketId): void
    {
        $this->client->delete([
            'index' => config('elasticsearch.ticket_index'),
            'id' => (string) $ticketId,
        ]);
    }

    public function search(string $query): array
    {
        $response = $this->client->search([
            'index' => config('elasticsearch.ticket_index'),
            'body' => [
                'query' => [
                    'multi_match' => [
                        'query' => $query,
                        'fields' => ['subject^2', 'description'],
                    ],
                ],
                'size' => 50,
            ],
        ])->asArray();

        return collect($response['hits']['hits'] ?? [])
            ->pluck('_id')
            ->map(fn (string $id): int => (int) $id)
            ->values()
            ->all();
    }
}
