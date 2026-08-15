<?php

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;

test('a ticket has many attachments', function () {
    $ticket = Ticket::factory()->create();
    $attachments = TicketAttachment::factory()->count(2)->for($ticket)->create();

    expect($ticket->attachments()->pluck('id')->sort()->values()->all())
        ->toBe($attachments->pluck('id')->sort()->values()->all());
});

test('an attachment belongs to a ticket and an uploader', function () {
    $ticket = Ticket::factory()->create();
    $uploader = User::factory()->create();

    $attachment = TicketAttachment::factory()->for($ticket)->create([
        'uploader_id' => $uploader->id,
    ]);

    expect($attachment->ticket->is($ticket))->toBeTrue()
        ->and($attachment->uploader->is($uploader))->toBeTrue();
});
