<?php

namespace App\Services\Search;

use App\Models\Ticket;

interface TicketSearchClient
{
    public function index(Ticket $ticket): void;

    public function delete(int $ticketId): void;

    /**
     * @return list<int> ticket ids ordered by relevance
     */
    public function search(string $query): array;
}
