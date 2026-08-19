<?php

use App\Models\Ticket;
use App\Models\User;
use App\Services\Search\TicketSearchClient;
use App\Services\Search\TicketSearchService;
use Elastic\Transport\Exception\NoNodeAvailableException;
use Spatie\Permission\Models\Permission;

// TicketSearchService::search() is supposed to treat Elasticsearch as
// nothing more than a source of ids: Ticket::visibleTo() is the only thing
// that actually decides what the caller gets back. This fakes the
// Elasticsearch boundary (via the app's own TicketSearchClient interface,
// not the vendor SDK) to prove that invariant holds even when Elasticsearch
// hands back an id the caller has no business seeing, which is exactly
// what a stale or compromised index would do.

test('search drops ids visibleTo would not have returned, even when Elasticsearch matched them', function () {
    Permission::findOrCreate('ticket.view-own');
    $user = User::factory()->create();
    $user->givePermissionTo('ticket.view-own');

    $own = Ticket::factory()->create(['requester_id' => $user->id]);
    $someoneElses = Ticket::factory()->create();

    $this->app->instance(TicketSearchClient::class, new class($own, $someoneElses) implements TicketSearchClient
    {
        public function __construct(private Ticket $own, private Ticket $someoneElses) {}

        public function index(Ticket $ticket): void {}

        public function delete(int $ticketId): void {}

        public function search(string $query): array
        {
            // Relevance order deliberately puts the ticket the user can't
            // see first, so a naive "just return whatever ES said" implementation
            // would leak it in position 0.
            return [$this->someoneElses->id, $this->own->id];
        }
    });

    $results = app(TicketSearchService::class)->search('anything', $user);

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($own->id);
});

test('search returns nothing when Elasticsearch matches nothing visible', function () {
    Permission::findOrCreate('ticket.view-own');
    $user = User::factory()->create();
    $user->givePermissionTo('ticket.view-own');

    $someoneElses = Ticket::factory()->create();

    $this->app->instance(TicketSearchClient::class, new class($someoneElses) implements TicketSearchClient
    {
        public function __construct(private Ticket $someoneElses) {}

        public function index(Ticket $ticket): void {}

        public function delete(int $ticketId): void {}

        public function search(string $query): array
        {
            return [$this->someoneElses->id];
        }
    });

    $results = app(TicketSearchService::class)->search('anything', $user);

    expect($results)->toHaveCount(0);
});

test('search returns nothing rather than throwing when Elasticsearch is unreachable', function () {
    Permission::findOrCreate('ticket.view-own');
    $user = User::factory()->create();
    $user->givePermissionTo('ticket.view-own');

    $this->app->instance(TicketSearchClient::class, new class implements TicketSearchClient
    {
        public function index(Ticket $ticket): void {}

        public function delete(int $ticketId): void {}

        public function search(string $query): array
        {
            throw new NoNodeAvailableException('No alive nodes.');
        }
    });

    $results = app(TicketSearchService::class)->search('anything', $user);

    expect($results)->toHaveCount(0);
});
