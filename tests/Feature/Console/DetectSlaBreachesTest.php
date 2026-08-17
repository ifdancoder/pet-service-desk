<?php

use App\Events\SlaBreached;
use App\Models\SlaPolicy;
use App\Models\SlaViolation;
use App\Models\Ticket;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;

test('creates a violation and dispatches SlaBreached for a breached open ticket', function () {
    Event::fake();

    $policy = SlaPolicy::factory()->create();
    $ticket = Ticket::factory()->create([
        'sla_due_at' => now()->subHour(),
        'category_id' => $policy->category_id,
        'priority' => $policy->priority,
    ]);

    Artisan::call('tickets:detect-sla-breaches');

    expect(SlaViolation::where('ticket_id', $ticket->id)->count())->toBe(1);
    Event::assertDispatched(SlaBreached::class, fn ($event) => $event->violation->ticket_id === $ticket->id);
});

test('does not create a duplicate violation on a second run', function () {
    Event::fake();

    $ticket = Ticket::factory()->create(['sla_due_at' => now()->subHour()]);

    Artisan::call('tickets:detect-sla-breaches');
    Artisan::call('tickets:detect-sla-breaches');

    expect(SlaViolation::where('ticket_id', $ticket->id)->count())->toBe(1);
});

test('ignores tickets with no sla_due_at or not yet breached', function () {
    Event::fake();

    Ticket::factory()->create(['sla_due_at' => null]);
    Ticket::factory()->create(['sla_due_at' => now()->addHour()]);

    Artisan::call('tickets:detect-sla-breaches');

    expect(SlaViolation::count())->toBe(0);
});

test('ignores closed tickets even if their sla_due_at has passed', function () {
    Event::fake();

    Ticket::factory()->closed()->create(['sla_due_at' => now()->subHour()]);

    Artisan::call('tickets:detect-sla-breaches');

    expect(SlaViolation::count())->toBe(0);
});
