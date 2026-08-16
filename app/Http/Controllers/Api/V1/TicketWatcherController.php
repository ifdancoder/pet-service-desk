<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTicketWatcherRequest;
use App\Http\Resources\Api\V1\TicketWatcherCollection;
use App\Http\Resources\Api\V1\TicketWatcherResource;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class TicketWatcherController extends Controller
{
    public function index(Ticket $ticket): TicketWatcherCollection
    {
        $this->authorize('view', $ticket);

        return new TicketWatcherCollection($ticket->watchers()->paginate(15));
    }

    public function store(StoreTicketWatcherRequest $request, Ticket $ticket, TicketService $service): JsonResponse
    {
        $watcher = User::findOrFail($request->validated('user_id'));
        $service->attachWatcher($ticket, $watcher);

        return (new TicketWatcherResource($watcher))->response()->setStatusCode(201);
    }

    public function destroy(Ticket $ticket, User $watcher, TicketService $service): Response
    {
        $this->authorize('update', $ticket);

        $service->detachWatcher($ticket, $watcher);

        return response()->noContent();
    }
}
