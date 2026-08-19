<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;

class SearchTicketsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Ticket::class);
    }

    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:2'],
        ];
    }
}
