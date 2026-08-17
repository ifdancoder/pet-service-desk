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
        // No real antivirus integration in this phase — this stub marks
        // every attachment safe. A later phase can replace this handle()
        // body with a real scanning service without touching any caller.
        $this->attachment->update(['scanned_at' => now()]);
    }
}
