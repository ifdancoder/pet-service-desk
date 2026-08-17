<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Ticket */
class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'description' => $this->description,
            'status' => $this->status->value,
            'priority' => $this->priority->value,
            'requester_id' => $this->requester_id,
            'assignee_id' => $this->assignee_id,
            'category_id' => $this->category_id,
            'department_id' => $this->department_id,
            'team_id' => $this->team_id,
            'sla_due_at' => $this->sla_due_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'requester' => new UserResource($this->whenLoaded('requester')),
            'assignee' => new UserResource($this->whenLoaded('assignee')),
            'category' => new TicketCategoryResource($this->whenLoaded('category')),
            'department' => new DepartmentResource($this->whenLoaded('department')),
            'team' => new TeamResource($this->whenLoaded('team')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
        ];
    }
}
