<?php

namespace App\Http\Controllers\Api\V1;

use App\DataTransferObjects\DepartmentData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreDepartmentRequest;
use App\Http\Requests\Api\V1\UpdateDepartmentRequest;
use App\Http\Resources\Api\V1\DepartmentCollection;
use App\Http\Resources\Api\V1\DepartmentResource;
use App\Models\Department;
use App\Services\DepartmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DepartmentController extends Controller
{
    public function index(): DepartmentCollection
    {
        return new DepartmentCollection(Department::query()->paginate(15));
    }

    public function show(Department $department): DepartmentResource
    {
        return new DepartmentResource($department);
    }

    public function store(StoreDepartmentRequest $request, DepartmentService $service): JsonResponse
    {
        $department = $service->create(DepartmentData::fromRequest($request));

        return (new DepartmentResource($department))->response()->setStatusCode(201);
    }

    public function update(UpdateDepartmentRequest $request, Department $department, DepartmentService $service): DepartmentResource
    {
        $department = $service->update($department, DepartmentData::fromRequest($request));

        return new DepartmentResource($department);
    }

    public function destroy(Request $request, Department $department, DepartmentService $service): Response
    {
        abort_unless($request->user()->can('user.manage'), 403);

        $service->delete($department);

        return response()->noContent();
    }
}
