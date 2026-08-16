<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\TicketPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeTicketPriorityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('changePriority', $this->route('ticket'));
    }

    public function rules(): array
    {
        return [
            'priority' => ['required', Rule::enum(TicketPriority::class)],
        ];
    }
}
