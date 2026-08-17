<?php

use App\DataTransferObjects\TicketCommentData;
use App\Events\TicketCommented;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketCommentService;
use Illuminate\Support\Facades\Event;

test('creating a comment dispatches TicketCommented', function () {
    Event::fake();

    $ticket = Ticket::factory()->create();
    $author = User::factory()->create();
    $data = new TicketCommentData(body: 'Hello', isInternal: false);

    $comment = app(TicketCommentService::class)->create($ticket, $data, $author);

    Event::assertDispatched(TicketCommented::class, fn ($event) => $event->comment->is($comment));
});
