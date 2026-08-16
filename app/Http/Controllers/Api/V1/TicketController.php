<?php

namespace App\Http\Controllers\Api\V1;

use App\DataTransferObjects\TicketData;
use App\Filters\TicketFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexTicketRequest;
use App\Http\Requests\Api\V1\StoreTicketRequest;
use App\Http\Requests\Api\V1\UpdateTicketRequest;
use App\Http\Resources\Api\V1\TicketCollection;
use App\Http\Resources\Api\V1\TicketResource;
use App\Models\SavedFilter;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;

class TicketController extends Controller
{
    public function index(IndexTicketRequest $request, TicketFilter $filter): TicketCollection
    {
        $filters = Arr::except($request->validated(), 'saved_filter_id');

        if ($savedFilterId = $request->validated('saved_filter_id')) {
            $savedFilter = SavedFilter::query()
                ->where('user_id', $request->user()->id)
                ->findOrFail($savedFilterId);

            $filters = array_merge($savedFilter->filters, $filters);
        }

        $query = Ticket::query()->visibleTo($request->user());
        $filter->apply($query, $filters);

        return new TicketCollection($query->latest()->paginate(15));
    }

    public function show(Ticket $ticket): TicketResource
    {
        $this->authorize('view', $ticket);

        return new TicketResource($ticket);
    }

    public function store(StoreTicketRequest $request, TicketService $service): JsonResponse
    {
        $ticket = $service->create(TicketData::fromRequest($request), $request->user());

        return (new TicketResource($ticket))->response()->setStatusCode(201);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket, TicketService $service): TicketResource
    {
        $ticket = $service->update($ticket, TicketData::fromRequest($request));

        return new TicketResource($ticket);
    }

    public function destroy(Ticket $ticket, TicketService $service): Response
    {
        $this->authorize('delete', $ticket);

        $service->delete($ticket);

        return response()->noContent();
    }
}
