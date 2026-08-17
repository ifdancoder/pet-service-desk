<?php

use App\Enums\TicketPriority;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Services\Sla\CalendarSlaCalculator;

test('calculates the due date from a category-specific active policy', function () {
    $category = TicketCategory::factory()->create();
    SlaPolicy::factory()->create([
        'category_id' => $category->id,
        'priority' => TicketPriority::High,
        'resolution_time_minutes' => 480,
        'active' => true,
    ]);

    $ticket = Ticket::factory()->create([
        'category_id' => $category->id,
        'priority' => TicketPriority::High,
    ]);

    $dueAt = (new CalendarSlaCalculator)->calculate($ticket);

    expect($dueAt->equalTo($ticket->created_at->addMinutes(480)))->toBeTrue();
});

test('falls back to the default (category_id null) policy when no category-specific one matches', function () {
    SlaPolicy::factory()->create([
        'category_id' => null,
        'priority' => TicketPriority::Low,
        'resolution_time_minutes' => 4320,
        'active' => true,
    ]);

    $ticket = Ticket::factory()->create(['priority' => TicketPriority::Low]);

    $dueAt = (new CalendarSlaCalculator)->calculate($ticket);

    expect($dueAt->equalTo($ticket->created_at->addMinutes(4320)))->toBeTrue();
});

test('returns null when no applicable policy exists', function () {
    $ticket = Ticket::factory()->create(['priority' => TicketPriority::Critical]);

    expect((new CalendarSlaCalculator)->calculate($ticket))->toBeNull();
});

test('ignores an inactive policy', function () {
    SlaPolicy::factory()->create([
        'category_id' => null,
        'priority' => TicketPriority::Normal,
        'active' => false,
    ]);

    $ticket = Ticket::factory()->create(['priority' => TicketPriority::Normal]);

    expect((new CalendarSlaCalculator)->calculate($ticket))->toBeNull();
});
