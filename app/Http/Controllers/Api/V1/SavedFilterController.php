<?php

namespace App\Http\Controllers\Api\V1;

use App\DataTransferObjects\SavedFilterData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreSavedFilterRequest;
use App\Http\Requests\Api\V1\UpdateSavedFilterRequest;
use App\Http\Resources\Api\V1\SavedFilterCollection;
use App\Http\Resources\Api\V1\SavedFilterResource;
use App\Models\SavedFilter;
use App\Services\SavedFilterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SavedFilterController extends Controller
{
    public function index(Request $request): SavedFilterCollection
    {
        return new SavedFilterCollection(
            SavedFilter::query()->where('user_id', $request->user()->id)->paginate(15)
        );
    }

    public function show(Request $request, SavedFilter $savedFilter): SavedFilterResource
    {
        abort_unless($savedFilter->user_id === $request->user()->id, 404);

        return new SavedFilterResource($savedFilter);
    }

    public function store(StoreSavedFilterRequest $request, SavedFilterService $service): JsonResponse
    {
        $savedFilter = $service->create(SavedFilterData::fromRequest($request), $request->user());

        return (new SavedFilterResource($savedFilter))->response()->setStatusCode(201);
    }

    public function update(UpdateSavedFilterRequest $request, SavedFilter $savedFilter, SavedFilterService $service): SavedFilterResource
    {
        $savedFilter = $service->update($savedFilter, SavedFilterData::fromRequest($request, $savedFilter));

        return new SavedFilterResource($savedFilter);
    }

    public function destroy(Request $request, SavedFilter $savedFilter, SavedFilterService $service): Response
    {
        abort_unless($savedFilter->user_id === $request->user()->id, 404);

        $service->delete($savedFilter);

        return response()->noContent();
    }
}
