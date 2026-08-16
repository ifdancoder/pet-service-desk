<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Ticket::class);
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(TicketStatus::class)],
            'priority' => ['sometimes', Rule::enum(TicketPriority::class)],
            'assignee_id' => ['sometimes', 'integer', 'exists:users,id'],
            'requester_id' => ['sometimes', 'integer', 'exists:users,id'],
            'team_id' => ['sometimes', 'integer', 'exists:teams,id'],
            'department_id' => ['sometimes', 'integer', 'exists:departments,id'],
            'category_id' => ['sometimes', 'integer', 'exists:ticket_categories,id'],
            'tag' => ['sometimes', 'string'],
            'saved_filter_id' => ['sometimes', 'integer', 'exists:saved_filters,id'],
        ];
    }
}
