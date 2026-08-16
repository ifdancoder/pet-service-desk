<?php

namespace App\DataTransferObjects;

use App\Enums\TicketPriority;
use App\Http\Requests\Api\V1\StoreTicketRequest;
use App\Http\Requests\Api\V1\UpdateTicketRequest;

final readonly class TicketData
{
    public function __construct(
        public string $subject,
        public string $description,
        public TicketPriority $priority,
        public int $categoryId,
        public int $departmentId,
        public ?int $teamId,
        public ?int $assigneeId,
    ) {}

    public static function fromRequest(StoreTicketRequest|UpdateTicketRequest $request): self
    {
        return new self(
            subject: $request->validated('subject'),
            description: $request->validated('description'),
            priority: TicketPriority::from($request->validated('priority')),
            categoryId: (int) $request->validated('category_id'),
            departmentId: (int) $request->validated('department_id'),
            teamId: $request->validated('team_id'),
            assigneeId: $request->validated('assignee_id'),
        );
    }
}
