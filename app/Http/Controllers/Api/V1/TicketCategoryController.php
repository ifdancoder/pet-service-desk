<?php

namespace App\Http\Controllers\Api\V1;

use App\DataTransferObjects\TicketCategoryData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTicketCategoryRequest;
use App\Http\Requests\Api\V1\UpdateTicketCategoryRequest;
use App\Http\Resources\Api\V1\TicketCategoryCollection;
use App\Http\Resources\Api\V1\TicketCategoryResource;
use App\Models\TicketCategory;
use App\Services\TicketCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TicketCategoryController extends Controller
{
    public function index(): TicketCategoryCollection
    {
        return new TicketCategoryCollection(TicketCategory::query()->paginate(15));
    }

    public function show(TicketCategory $ticketCategory): TicketCategoryResource
    {
        return new TicketCategoryResource($ticketCategory);
    }

    public function store(StoreTicketCategoryRequest $request, TicketCategoryService $service): JsonResponse
    {
        $category = $service->create(TicketCategoryData::fromRequest($request));

        return (new TicketCategoryResource($category))->response()->setStatusCode(201);
    }

    public function update(UpdateTicketCategoryRequest $request, TicketCategory $ticketCategory, TicketCategoryService $service): TicketCategoryResource
    {
        $ticketCategory = $service->update($ticketCategory, TicketCategoryData::fromRequest($request));

        return new TicketCategoryResource($ticketCategory);
    }

    public function destroy(Request $request, TicketCategory $ticketCategory, TicketCategoryService $service): Response
    {
        abort_unless($request->user()->can('user.manage'), 403);

        $service->delete($ticketCategory);

        return response()->noContent();
    }
}
