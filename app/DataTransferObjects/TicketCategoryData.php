<?php

namespace App\DataTransferObjects;

use App\Enums\TicketPriority;
use App\Http\Requests\Api\V1\StoreTicketCategoryRequest;
use App\Http\Requests\Api\V1\UpdateTicketCategoryRequest;

final readonly class TicketCategoryData
{
    public function __construct(
        public string $name,
        public bool $active,
        public ?TicketPriority $defaultPriority,
    ) {}

    public static function fromRequest(StoreTicketCategoryRequest|UpdateTicketCategoryRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            active: (bool) $request->validated('active'),
            defaultPriority: $request->validated('default_priority')
                ? TicketPriority::from($request->validated('default_priority'))
                : null,
        );
    }
}
