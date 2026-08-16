<?php

namespace App\Http\Controllers\Api\V1;

use App\DataTransferObjects\TicketAttachmentData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTicketAttachmentRequest;
use App\Http\Resources\Api\V1\TicketAttachmentCollection;
use App\Http\Resources\Api\V1\TicketAttachmentResource;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Services\TicketAttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class TicketAttachmentController extends Controller
{
    public function index(Ticket $ticket): TicketAttachmentCollection
    {
        $this->authorize('view', $ticket);

        return new TicketAttachmentCollection($ticket->attachments()->paginate(15));
    }

    public function store(StoreTicketAttachmentRequest $request, Ticket $ticket, TicketAttachmentService $service): JsonResponse
    {
        $attachment = $service->store($ticket, TicketAttachmentData::fromRequest($request), $request->user());

        return (new TicketAttachmentResource($attachment))->response()->setStatusCode(201);
    }

    public function destroy(Ticket $ticket, TicketAttachment $attachment, TicketAttachmentService $service): Response
    {
        $this->authorize('update', $ticket);
        abort_unless($attachment->ticket_id === $ticket->id, 404);

        $service->delete($attachment);

        return response()->noContent();
    }

    public function download(Ticket $ticket, TicketAttachment $attachment): RedirectResponse
    {
        $this->authorize('view', $ticket);
        abort_unless($attachment->ticket_id === $ticket->id, 404);

        $url = Storage::disk($attachment->disk)->temporaryUrl(
            $attachment->path,
            now()->addMinutes(5)
        );

        return redirect()->away($url);
    }
}
