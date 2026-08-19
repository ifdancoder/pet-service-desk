<?php

use App\Models\AuditLog;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketComment;
use App\Models\User;

test('updating a ticket creates an audit log entry', function () {
    $actor = User::factory()->create();
    $this->actingAs($actor);
    $ticket = Ticket::factory()->create(['subject' => 'Old subject']);

    $ticket->update(['subject' => 'New subject']);

    expect(AuditLog::count())->toBe(1);
    $log = AuditLog::first();
    expect($log->auditable_type)->toBe(Ticket::class)
        ->and($log->auditable_id)->toBe($ticket->id)
        ->and($log->user_id)->toBe($actor->id)
        ->and($log->changes)->toBe(['subject' => ['old' => 'Old subject', 'new' => 'New subject']]);
});

test('updating a ticket comment creates an audit log entry', function () {
    $actor = User::factory()->create();
    $this->actingAs($actor);
    $comment = TicketComment::factory()->create(['body' => 'Old body']);

    $comment->update(['body' => 'New body']);

    $log = AuditLog::first();
    expect($log->auditable_type)->toBe(TicketComment::class)
        ->and($log->auditable_id)->toBe($comment->id)
        ->and($log->changes)->toBe(['body' => ['old' => 'Old body', 'new' => 'New body']]);
});

test('updating a ticket attachment creates an audit log entry', function () {
    $actor = User::factory()->create();
    $this->actingAs($actor);
    $attachment = TicketAttachment::factory()->create(['original_name' => 'old.pdf']);

    $attachment->update(['original_name' => 'new.pdf']);

    $log = AuditLog::first();
    expect($log->auditable_type)->toBe(TicketAttachment::class)
        ->and($log->auditable_id)->toBe($attachment->id)
        ->and($log->changes)->toBe(['original_name' => ['old' => 'old.pdf', 'new' => 'new.pdf']]);
});

test('updating a user creates an audit log entry', function () {
    $actor = User::factory()->create();
    $this->actingAs($actor);
    $target = User::factory()->create(['name' => 'Old Name']);

    $target->update(['name' => 'New Name']);

    $log = AuditLog::first();
    expect($log->auditable_type)->toBe(User::class)
        ->and($log->auditable_id)->toBe($target->id)
        ->and($log->changes)->toBe(['name' => ['old' => 'Old Name', 'new' => 'New Name']]);
});

test('updating an sla policy creates an audit log entry', function () {
    $actor = User::factory()->create();
    $this->actingAs($actor);
    $policy = SlaPolicy::factory()->create(['response_time_minutes' => 60]);

    $policy->update(['response_time_minutes' => 30]);

    $log = AuditLog::first();
    expect($log->auditable_type)->toBe(SlaPolicy::class)
        ->and($log->auditable_id)->toBe($policy->id)
        ->and($log->changes)->toBe(['response_time_minutes' => ['old' => 60, 'new' => 30]]);
});

test('creating a ticket does not create an audit log entry', function () {
    Ticket::factory()->create();

    expect(AuditLog::count())->toBe(0);
});

test('updated_at never appears as a key in the changes diff', function () {
    $ticket = Ticket::factory()->create(['subject' => 'Old subject']);

    $ticket->update(['subject' => 'New subject']);

    expect(AuditLog::first()->changes)->not->toHaveKey('updated_at');
});

test('a console-triggered update has a null user_id', function () {
    $ticket = Ticket::factory()->create(['subject' => 'Old subject']);

    $ticket->update(['subject' => 'New subject']);

    expect(AuditLog::first()->user_id)->toBeNull();
});
