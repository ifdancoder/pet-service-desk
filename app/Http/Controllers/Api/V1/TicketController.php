<?php

namespace App\Http\Controllers\Api\V1;

use App\Filters\TicketFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexTicketRequest;
use App\Http\Resources\Api\V1\TicketCollection;
use App\Http\Resources\Api\V1\TicketResource;
use App\Models\SavedFilter;
use App\Models\Ticket;
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
}
