<?php

use App\DataTransferObjects\TicketData;
use App\Enums\TicketPriority;
use App\Models\Department;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Services\TicketService;

test('creating a ticket sets sla_due_at from the applicable policy', function () {
    SlaPolicy::factory()->create([
        'category_id' => null,
        'priority' => TicketPriority::High,
        'resolution_time_minutes' => 480,
        'active' => true,
    ]);

    $requester = User::factory()->create();
    $category = TicketCategory::factory()->create();
    $department = Department::factory()->create();

    $data = new TicketData(
        subject: 'Test',
        description: 'Test description',
        priority: TicketPriority::High,
        categoryId: $category->id,
        departmentId: $department->id,
        teamId: null,
        assigneeId: null,
    );

    $ticket = app(TicketService::class)->create($data, $requester);

    expect($ticket->sla_due_at)->not->toBeNull()
        ->and($ticket->sla_due_at->equalTo($ticket->created_at->addMinutes(480)))->toBeTrue();
});

test('creating a ticket with no applicable policy leaves sla_due_at null', function () {
    $requester = User::factory()->create();
    $category = TicketCategory::factory()->create();
    $department = Department::factory()->create();

    $data = new TicketData(
        subject: 'Test',
        description: 'Test description',
        priority: TicketPriority::Critical,
        categoryId: $category->id,
        departmentId: $department->id,
        teamId: null,
        assigneeId: null,
    );

    $ticket = app(TicketService::class)->create($data, $requester);

    expect($ticket->sla_due_at)->toBeNull();
});

test('changing priority recalculates sla_due_at anchored to created_at', function () {
    SlaPolicy::factory()->create([
        'category_id' => null,
        'priority' => TicketPriority::Low,
        'resolution_time_minutes' => 4320,
        'active' => true,
    ]);
    SlaPolicy::factory()->create([
        'category_id' => null,
        'priority' => TicketPriority::Critical,
        'resolution_time_minutes' => 120,
        'active' => true,
    ]);

    $ticket = Ticket::factory()->create(['priority' => TicketPriority::Low]);

    $ticket = app(TicketService::class)->changePriority($ticket, TicketPriority::Critical);

    expect($ticket->sla_due_at->equalTo($ticket->created_at->addMinutes(120)))->toBeTrue();
});
