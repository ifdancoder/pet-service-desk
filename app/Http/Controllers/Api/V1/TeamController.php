<?php

namespace App\Http\Controllers\Api\V1;

use App\DataTransferObjects\TeamData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTeamRequest;
use App\Http\Requests\Api\V1\UpdateTeamRequest;
use App\Http\Resources\Api\V1\TeamCollection;
use App\Http\Resources\Api\V1\TeamResource;
use App\Models\Team;
use App\Services\TeamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TeamController extends Controller
{
    public function index(): TeamCollection
    {
        return new TeamCollection(Team::query()->paginate(15));
    }

    public function show(Team $team): TeamResource
    {
        return new TeamResource($team);
    }

    public function store(StoreTeamRequest $request, TeamService $service): JsonResponse
    {
        $team = $service->create(TeamData::fromRequest($request));

        return (new TeamResource($team))->response()->setStatusCode(201);
    }

    public function update(UpdateTeamRequest $request, Team $team, TeamService $service): TeamResource
    {
        $team = $service->update($team, TeamData::fromRequest($request));

        return new TeamResource($team);
    }

    public function destroy(Request $request, Team $team, TeamService $service): Response
    {
        abort_unless($request->user()->can('org.manage'), 403);

        $service->delete($team);

        return response()->noContent();
    }
}
