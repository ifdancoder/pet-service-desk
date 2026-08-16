<?php

namespace App\DataTransferObjects;

use App\Http\Requests\Api\V1\StoreTicketAttachmentRequest;
use Illuminate\Http\UploadedFile;

final readonly class TicketAttachmentData
{
    public function __construct(
        public UploadedFile $file,
    ) {}

    public static function fromRequest(StoreTicketAttachmentRequest $request): self
    {
        return new self(file: $request->file('file'));
    }
}
