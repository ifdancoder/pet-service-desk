<?php

use App\Models\SlaPolicy;
use App\Models\SlaViolation;
use App\Models\Ticket;

test('an sla violation belongs to a ticket and an sla policy', function () {
    $ticket = Ticket::factory()->create();
    $policy = SlaPolicy::factory()->create();

    $violation = SlaViolation::factory()->create([
        'ticket_id' => $ticket->id,
        'sla_policy_id' => $policy->id,
    ]);

    expect($violation->ticket->is($ticket))->toBeTrue()
        ->and($violation->slaPolicy->is($policy))->toBeTrue();
});

test('a ticket has many sla violations', function () {
    $ticket = Ticket::factory()->create();
    $violation = SlaViolation::factory()->create(['ticket_id' => $ticket->id]);

    expect($ticket->violations()->pluck('id')->all())->toBe([$violation->id]);
});

test('an sla violation can exist without an sla policy', function () {
    $violation = SlaViolation::factory()->create(['sla_policy_id' => null]);

    expect($violation->slaPolicy)->toBeNull();
});
