<?php

namespace App\Http\Controllers\Api\V1;

use App\DataTransferObjects\SlaPolicyData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreSlaPolicyRequest;
use App\Http\Requests\Api\V1\UpdateSlaPolicyRequest;
use App\Http\Resources\Api\V1\SlaPolicyCollection;
use App\Http\Resources\Api\V1\SlaPolicyResource;
use App\Models\SlaPolicy;
use App\Services\SlaPolicyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class SlaPolicyController extends Controller
{
    public function index(): SlaPolicyCollection
    {
        $this->authorize('viewAny', SlaPolicy::class);

        return new SlaPolicyCollection(SlaPolicy::query()->paginate(15));
    }

    public function show(SlaPolicy $slaPolicy): SlaPolicyResource
    {
        $this->authorize('view', $slaPolicy);

        return new SlaPolicyResource($slaPolicy);
    }

    public function store(StoreSlaPolicyRequest $request, SlaPolicyService $service): JsonResponse
    {
        $slaPolicy = $service->create(SlaPolicyData::fromRequest($request));

        return (new SlaPolicyResource($slaPolicy))->response()->setStatusCode(201);
    }

    public function update(UpdateSlaPolicyRequest $request, SlaPolicy $slaPolicy, SlaPolicyService $service): SlaPolicyResource
    {
        $slaPolicy = $service->update($slaPolicy, SlaPolicyData::fromRequest($request));

        return new SlaPolicyResource($slaPolicy);
    }

    public function destroy(SlaPolicy $slaPolicy, SlaPolicyService $service): Response
    {
        $this->authorize('delete', $slaPolicy);

        $service->delete($slaPolicy);

        return response()->noContent();
    }
}
