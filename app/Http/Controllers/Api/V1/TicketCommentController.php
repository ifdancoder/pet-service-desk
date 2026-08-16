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
use Illuminate\Http\Response;

class TicketCommentController extends Controller
{
    public function index(Ticket $ticket): TicketCommentCollection
    {
        $this->authorize('view', $ticket);

        return new TicketCommentCollection($ticket->comments()->paginate(15));
    }

    public function store(StoreTicketCommentRequest $request, Ticket $ticket, TicketCommentService $service): JsonResponse
    {
        $comment = $service->create($ticket, TicketCommentData::fromRequest($request), $request->user());

        return (new TicketCommentResource($comment))->response()->setStatusCode(201);
    }

    public function destroy(Ticket $ticket, TicketComment $comment, TicketCommentService $service): Response
    {
        $this->authorize('delete', $comment);

        $service->delete($comment);

        return response()->noContent();
    }
}
