<?php

use App\DataTransferObjects\TicketAttachmentData;
use App\Jobs\ScanAttachment;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use App\Services\TicketAttachmentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

test('storing an attachment dispatches ScanAttachment', function () {
    Queue::fake();
    Storage::fake('s3');

    $ticket = Ticket::factory()->create();
    $uploader = User::factory()->create();
    $file = UploadedFile::fake()->create('report.pdf', 100, 'application/pdf');
    $data = new TicketAttachmentData(file: $file);

    $attachment = app(TicketAttachmentService::class)->store($ticket, $data, $uploader);

    Queue::assertPushed(ScanAttachment::class, fn ($job) => $job->attachment->is($attachment));
});

test('the job marks the attachment scanned', function () {
    $attachment = TicketAttachment::factory()->create(['scanned_at' => null]);

    (new ScanAttachment($attachment))->handle();

    expect($attachment->fresh()->scanned_at)->not->toBeNull();
});
