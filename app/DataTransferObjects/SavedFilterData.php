<?php

namespace App\DataTransferObjects;

use App\Http\Requests\Api\V1\StoreSavedFilterRequest;
use App\Http\Requests\Api\V1\UpdateSavedFilterRequest;

final readonly class SavedFilterData
{
    public function __construct(
        public string $name,
        public array $filters,
    ) {}

    public static function fromRequest(StoreSavedFilterRequest|UpdateSavedFilterRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            filters: $request->validated('filters'),
        );
    }
}
