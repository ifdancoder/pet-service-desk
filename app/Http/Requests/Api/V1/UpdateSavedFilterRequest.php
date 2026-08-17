<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UpdateSavedFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $savedFilter = $this->route('saved_filter');

        return $savedFilter->user_id === $this->user()->id;
    }

    protected function failedAuthorization(): void
    {
        // A saved filter is inherently personal: another user's filter must
        // 404 (not 403) so its existence isn't revealed, matching the
        // abort_unless(..., 404) checks in show()/destroy().
        throw new NotFoundHttpException;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'filters' => ['sometimes', 'array'],
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
