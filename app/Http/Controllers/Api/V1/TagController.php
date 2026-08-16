<?php

namespace App\Http\Controllers\Api\V1;

use App\DataTransferObjects\TagData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTagRequest;
use App\Http\Requests\Api\V1\UpdateTagRequest;
use App\Http\Resources\Api\V1\TagCollection;
use App\Http\Resources\Api\V1\TagResource;
use App\Models\Tag;
use App\Services\TagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TagController extends Controller
{
    public function index(): TagCollection
    {
        return new TagCollection(Tag::query()->paginate(15));
    }

    public function show(Tag $tag): TagResource
    {
        return new TagResource($tag);
    }

    public function store(StoreTagRequest $request, TagService $service): JsonResponse
    {
        $tag = $service->create(TagData::fromRequest($request));

        return (new TagResource($tag))->response()->setStatusCode(201);
    }

    public function update(UpdateTagRequest $request, Tag $tag, TagService $service): TagResource
    {
        $tag = $service->update($tag, TagData::fromRequest($request));

        return new TagResource($tag);
    }

    public function destroy(Request $request, Tag $tag, TagService $service): Response
    {
        abort_unless($request->user()->can('user.manage'), 403);

        $service->delete($tag);

        return response()->noContent();
    }
}
