<?php

namespace App\Jobs;

use App\Models\TicketAttachment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScanAttachment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $deleteWhenMissingModels = true;

    public function __construct(public readonly TicketAttachment $attachment) {}

    public function handle(): void
    {
        // No real antivirus integration in this phase. This stub just marks
        // every attachment safe. A later phase can swap in a real scanning
        // service by replacing this handle() body, no caller has to change.
        $this->attachment->update(['scanned_at' => now()]);
    }
}
