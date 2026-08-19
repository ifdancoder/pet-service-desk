<?php

namespace App\Services\Search;

use App\Models\Ticket;
use App\Models\User;
use Elastic\Elasticsearch\Exception\ElasticsearchException;
use Elastic\Transport\Exception\TransportException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class TicketSearchService
{
    public function __construct(private readonly TicketSearchClient $client) {}

    public function index(Ticket $ticket): void
    {
        $this->client->index($ticket);
    }

    public function delete(int $ticketId): void
    {
        $this->client->delete($ticketId);
    }

    /**
     * Full-text search over subject/description, restricted to tickets the
     * given user can see. Elasticsearch only picks which ids match and in
     * what order, Ticket::visibleTo() still decides what the caller is
     * allowed to see, so a stale or unindexed document can never leak a
     * ticket through search that the normal listing would hide.
     *
     * Elasticsearch being unreachable, or the index not existing yet,
     * degrades to an empty result instead of failing the request, same as
     * the indexing jobs degrade to a logged warning.
     *
     * @return Collection<int, Ticket>
     */
    public function search(string $query, User $user): Collection
    {
        try {
            $orderedIds = $this->client->search($query);
        } catch (TransportException|ElasticsearchException $exception) {
            Log::warning('Ticket search unavailable.', [
                'query' => $query,
                'exception' => $exception->getMessage(),
            ]);

            return new Collection;
        }

        if ($orderedIds === []) {
            return new Collection;
        }

        $tickets = Ticket::query()
            ->visibleTo($user)
            ->whereIn('id', $orderedIds)
            ->with(['requester', 'assignee', 'category', 'department', 'team', 'tags'])
            ->get()
            ->keyBy('id');

        return new Collection(
            collect($orderedIds)
                ->map(fn (int $id) => $tickets->get($id))
                ->filter()
                ->values()
                ->all()
        );
    }
}
