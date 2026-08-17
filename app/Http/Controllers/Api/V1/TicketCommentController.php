<?php

namespace App\Http\Controllers\Api\V1;

use App\DataTransferObjects\TicketCommentData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTicketCommentRequest;
use App\Http\Resources\Api\V1\TicketCommentCollection;
use App\Http\Resources\Api\V1\TicketCommentResource;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Services\TicketCommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TicketCommentController extends Controller
{
    public function index(Request $request, Ticket $ticket): TicketCommentCollection
    {
        $this->authorize('view', $ticket);

        return new TicketCommentCollection($ticket->comments()->visibleTo($request->user())->paginate(15));
    }

    public function store(StoreTicketCommentRequest $request, Ticket $ticket, TicketCommentService $service): JsonResponse
    {
        $comment = $service->create($ticket, TicketCommentData::fromRequest($request), $request->user());

        return (new TicketCommentResource($comment))->response()->setStatusCode(201);
    }

    public function destroy(Ticket $ticket, TicketComment $comment, TicketCommentService $service): Response
    {
        $this->authorize('delete', $comment);
        abort_unless($comment->ticket_id === $ticket->id, 404);

        $service->delete($comment);

        return response()->noContent();
    }
}
