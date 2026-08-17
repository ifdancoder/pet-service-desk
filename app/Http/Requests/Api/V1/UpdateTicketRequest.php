<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\TicketPriority;
use App\Rules\ProhibitedWithoutPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('ticket'));
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['sometimes', Rule::enum(TicketPriority::class), Rule::prohibitedIf(fn () => ! $this->user()->can('ticket.change-priority'))],
            'category_id' => ['required', 'integer', Rule::exists('ticket_categories', 'id')->where('active', true)],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'team_id' => ['nullable', 'integer', 'exists:teams,id', new ProhibitedWithoutPermission($this->user(), 'ticket.assign')],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id', new ProhibitedWithoutPermission($this->user(), 'ticket.assign')],
        ];
    }
}
