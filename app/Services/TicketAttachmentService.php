<?php

namespace App\Services;

use App\DataTransferObjects\TicketAttachmentData;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TicketAttachmentService
{
    public function store(Ticket $ticket, TicketAttachmentData $data, User $uploader): TicketAttachment
    {
        $file = $data->file;
        $path = $file->storeAs(
            "tickets/{$ticket->id}",
            Str::uuid().'-'.$file->getClientOriginalName(),
            's3'
        );

        return $ticket->attachments()->create([
            'uploader_id' => $uploader->id,
            'disk' => 's3',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);
    }

    public function delete(TicketAttachment $attachment): void
    {
        Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->delete();
    }
}
