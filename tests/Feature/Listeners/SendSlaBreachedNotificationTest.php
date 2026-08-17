<?php

use App\Enums\UserRole;
use App\Events\SlaBreached;
use App\Models\Department;
use App\Models\SlaViolation;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\SlaBreachedNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Notification;

test('notifies the team_leads of the ticket team', function () {
    (new RolePermissionSeeder)->run();
    Notification::fake();

    $department = Department::factory()->create();
    $team = Team::factory()->for($department)->create();
    $teamLead = User::factory()->create();
    $teamLead->assignRole(UserRole::TeamLead->value);
    $team->users()->attach($teamLead);

    $ticket = Ticket::factory()->create(['department_id' => $department->id, 'team_id' => $team->id]);
    $violation = SlaViolation::factory()->create(['ticket_id' => $ticket->id]);

    event(new SlaBreached($violation));

    Notification::assertSentTo($teamLead, SlaBreachedNotification::class);
});

test('falls back to department team_leads when the ticket has no team', function () {
    (new RolePermissionSeeder)->run();
    Notification::fake();

    $department = Department::factory()->create();
    $teamLead = User::factory()->for($department)->create();
    $teamLead->assignRole(UserRole::TeamLead->value);

    $ticket = Ticket::factory()->create(['department_id' => $department->id, 'team_id' => null]);
    $violation = SlaViolation::factory()->create(['ticket_id' => $ticket->id]);

    event(new SlaBreached($violation));

    Notification::assertSentTo($teamLead, SlaBreachedNotification::class);
});
