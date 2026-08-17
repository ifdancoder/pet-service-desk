<?php

namespace App\DataTransferObjects;

use App\Http\Requests\Api\V1\StoreSavedFilterRequest;
use App\Http\Requests\Api\V1\UpdateSavedFilterRequest;
use App\Models\SavedFilter;

final readonly class SavedFilterData
{
    public function __construct(
        public string $name,
        public array $filters,
    ) {}

    public static function fromRequest(StoreSavedFilterRequest|UpdateSavedFilterRequest $request, ?SavedFilter $savedFilter = null): self
    {
        // On a partial PATCH, a key omitted from the request payload falls
        // back to the saved filter's current value instead of being
        // required, mirroring TicketData::fromRequest's pattern.
        $name = $request->has('name')
            ? $request->validated('name')
            : $savedFilter->name;

        $filters = $request->has('filters')
            ? $request->validated('filters')
            : $savedFilter->filters;

        return new self(
            name: $name,
            filters: $filters,
        );
    }
}
