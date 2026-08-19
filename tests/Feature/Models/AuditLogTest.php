<?php

use App\Models\AuditLog;
use App\Models\Ticket;
use App\Models\User;

test('an audit log belongs to its auditable record and the acting user, and casts changes to an array', function () {
    $ticket = Ticket::factory()->create();
    $user = User::factory()->create();

    $log = AuditLog::factory()
        ->for($ticket, 'auditable')
        ->create([
            'user_id' => $user->id,
            'changes' => ['subject' => ['old' => 'Old subject', 'new' => 'New subject']],
        ]);

    expect($log->fresh()->auditable->is($ticket))->toBeTrue()
        ->and($log->fresh()->user->is($user))->toBeTrue()
        ->and($log->fresh()->changes)->toBe(['subject' => ['old' => 'Old subject', 'new' => 'New subject']]);
});

test('an audit log has no managed updated_at column', function () {
    expect(AuditLog::UPDATED_AT)->toBeNull();
});
