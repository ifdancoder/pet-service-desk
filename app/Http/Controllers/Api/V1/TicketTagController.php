<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateTicketTagsRequest;
use App\Http\Resources\Api\V1\TagCollection;
use App\Models\Ticket;
use App\Services\TicketService;

class TicketTagController extends Controller
{
    public function update(UpdateTicketTagsRequest $request, Ticket $ticket, TicketService $service): TagCollection
    {
        $ticket = $service->syncTags($ticket, $request->validated('tags'));

        return new TagCollection($ticket->tags);
    }
}
