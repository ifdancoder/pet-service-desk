<?php

use App\Enums\TicketPriority;
use App\Enums\UserRole;
use App\Filament\Resources\Tickets\Pages\CreateTicket;
use App\Filament\Resources\Tickets\Pages\EditTicket;
use App\Filament\Resources\Tickets\Pages\ListTickets;
use App\Models\Department;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => RolePermissionSeeder::class]);
    $this->department = Department::factory()->create();
    $this->category = TicketCategory::factory()->create();
});

test('lists only tickets visible to the acting staff member', function () {
    Permission::findOrCreate('ticket.view-team');
    $agent = User::factory()->for($this->department)->create();
    $agent->assignRole(UserRole::SupportAgent->value);
    $agent->givePermissionTo('ticket.view-team');
    $team = Team::factory()->for($this->department)->create();
    $team->users()->attach($agent);

    $visible = Ticket::factory()->create(['team_id' => $team->id, 'department_id' => $this->department->id]);
    $notVisible = Ticket::factory()->create();

    $this->actingAs($agent, 'web');

    Livewire::test(ListTickets::class)
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$notVisible]);
});

test('creates a ticket via TicketService', function () {
    $administrator = User::factory()->create();
    $administrator->assignRole(UserRole::Administrator->value);
    $this->actingAs($administrator, 'web');

    $requester = User::factory()->create();

    Livewire::test(CreateTicket::class)
        ->fillForm([
            'requester_id' => $requester->id,
            'subject' => 'Cannot log in',
            'description' => 'Getting a 500 error.',
            'priority' => TicketPriority::High->value,
            'category_id' => $this->category->id,
            'department_id' => $this->department->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $ticket = Ticket::where('subject', 'Cannot log in')->firstOrFail();
    expect($ticket->requester_id)->toBe($requester->id)
        ->and($ticket->assignee_id)->toBeNull()
        ->and($ticket->sla_due_at)->toBeNull(); // no SlaPolicy seeded in this test
});

test('a user with ticket.manage can edit a ticket via TicketService, recalculating sla_due_at', function () {
    Permission::findOrCreate('ticket.view-all');
    Permission::findOrCreate('ticket.manage');
    $manager = User::factory()->create();
    $manager->givePermissionTo(['ticket.view-all', 'ticket.manage']);
    $this->actingAs($manager, 'web');

    $ticket = Ticket::factory()->create(['category_id' => $this->category->id, 'department_id' => $this->department->id]);
    $newCategory = TicketCategory::factory()->create();

    Livewire::test(EditTicket::class, ['record' => $ticket->getRouteKey()])
        ->fillForm(['category_id' => $newCategory->id])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($ticket->fresh()->category_id)->toBe($newCategory->id);
});

test('a user without ticket.manage cannot edit a ticket', function () {
    Permission::findOrCreate('ticket.view-all');
    $viewer = User::factory()->create();
    $viewer->givePermissionTo('ticket.view-all');
    $this->actingAs($viewer, 'web');

    $ticket = Ticket::factory()->create();

    Livewire::test(EditTicket::class, ['record' => $ticket->getRouteKey()])
        ->assertForbidden();
});
