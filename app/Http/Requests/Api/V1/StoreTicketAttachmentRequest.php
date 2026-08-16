<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('ticket'));
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240',
                'mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt,csv',
            ],
        ];
    }
}
