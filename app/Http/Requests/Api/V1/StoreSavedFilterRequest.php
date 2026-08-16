<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSavedFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'filters' => ['required', 'array'],
            'filters.status' => ['sometimes', Rule::enum(TicketStatus::class)],
            'filters.priority' => ['sometimes', Rule::enum(TicketPriority::class)],
            'filters.assignee_id' => ['sometimes', 'integer', 'exists:users,id'],
            'filters.requester_id' => ['sometimes', 'integer', 'exists:users,id'],
            'filters.team_id' => ['sometimes', 'integer', 'exists:teams,id'],
            'filters.department_id' => ['sometimes', 'integer', 'exists:departments,id'],
            'filters.category_id' => ['sometimes', 'integer', 'exists:ticket_categories,id'],
            'filters.tag' => ['sometimes', 'string'],
        ];
    }
}
