<?php

namespace App\DataTransferObjects;

use App\Http\Requests\Api\V1\StoreTeamRequest;
use App\Http\Requests\Api\V1\UpdateTeamRequest;

final readonly class TeamData
{
    public function __construct(
        public string $name,
        public int $departmentId,
    ) {}

    public static function fromRequest(StoreTeamRequest|UpdateTeamRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            departmentId: (int) $request->validated('department_id'),
        );
    }
}
