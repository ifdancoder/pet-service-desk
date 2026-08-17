<?php

namespace App\DataTransferObjects;

use App\Enums\TicketPriority;
use App\Http\Requests\Api\V1\StoreTicketRequest;
use App\Http\Requests\Api\V1\UpdateTicketRequest;
use App\Models\Ticket;

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

    public static function fromRequest(StoreTicketRequest|UpdateTicketRequest $request, ?Ticket $ticket = null): self
    {
        // `priority` is required on create (StoreTicketRequest), so the "omitted"
        // branch is only ever reached on update, where $ticket is always given.
        $priority = $request->has('priority')
            ? TicketPriority::from($request->validated('priority'))
            : $ticket->priority;

        $teamId = $request->has('team_id')
            ? $request->validated('team_id')
            : $ticket?->team_id;

        $assigneeId = $request->has('assignee_id')
            ? $request->validated('assignee_id')
            : $ticket?->assignee_id;

        return new self(
            subject: $request->validated('subject'),
            description: $request->validated('description'),
            priority: $priority,
            categoryId: (int) $request->validated('category_id'),
            departmentId: (int) $request->validated('department_id'),
            teamId: $teamId,
            assigneeId: $assigneeId,
        );
    }
}
