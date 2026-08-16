<?php

namespace App\DataTransferObjects;

use App\Enums\TicketPriority;
use App\Http\Requests\Api\V1\StoreSlaPolicyRequest;
use App\Http\Requests\Api\V1\UpdateSlaPolicyRequest;

final readonly class SlaPolicyData
{
    public function __construct(
        public ?int $categoryId,
        public TicketPriority $priority,
        public int $responseTimeMinutes,
        public int $resolutionTimeMinutes,
        public bool $active,
    ) {}

    public static function fromRequest(StoreSlaPolicyRequest|UpdateSlaPolicyRequest $request): self
    {
        return new self(
            categoryId: $request->validated('category_id'),
            priority: TicketPriority::from($request->validated('priority')),
            responseTimeMinutes: (int) $request->validated('response_time_minutes'),
            resolutionTimeMinutes: (int) $request->validated('resolution_time_minutes'),
            active: (bool) $request->validated('active'),
        );
    }
}
