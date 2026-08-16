<?php

namespace App\DataTransferObjects;

use App\Http\Requests\Api\V1\StoreDepartmentRequest;
use App\Http\Requests\Api\V1\UpdateDepartmentRequest;

final readonly class DepartmentData
{
    public function __construct(
        public string $name,
    ) {}

    public static function fromRequest(StoreDepartmentRequest|UpdateDepartmentRequest $request): self
    {
        return new self(name: $request->validated('name'));
    }
}
