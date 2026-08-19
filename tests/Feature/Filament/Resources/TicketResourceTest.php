<?php

use App\Enums\TicketPriority;
use App\Enums\UserRole;
use App\Events\TicketCreated;
use App\Filament\Resources\Tickets\Pages\CreateTicket;
use App\Filament\Resources\Tickets\Pages\EditTicket;
use App\Filament\Resources\Tickets\Pages\ListTickets;
use App\Filament\Resources\Tickets\TicketResource;
use App\Models\Department;
use App\Models\SlaPolicy;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
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

    // Only TicketService::create() dispatches TicketCreated. Filament's own
    // default record creation would not, so this proves the service ran.
    Event::fake();

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

    Event::assertDispatched(TicketCreated::class, fn ($event) => $event->ticket->is($ticket));
});

test('a user with ticket.manage can edit a ticket via TicketService, recalculating sla_due_at', function () {
    Permission::findOrCreate('ticket.view-all');
    Permission::findOrCreate('ticket.manage');
    $manager = User::factory()->create();
    $manager->givePermissionTo(['ticket.view-all', 'ticket.manage']);
    $this->actingAs($manager, 'web');

    $ticket = Ticket::factory()->create([
        'category_id' => $this->category->id,
        'department_id' => $this->department->id,
        'priority' => TicketPriority::High,
        'sla_due_at' => null,
    ]);
    $newCategory = TicketCategory::factory()->create();

    // Only a policy for the NEW category, so a recalculated sla_due_at can only
    // come from TicketService::update() re-running CalendarSlaCalculator after
    // the category change. Filament's default Eloquent save would leave it null.
    SlaPolicy::factory()->create([
        'category_id' => $newCategory->id,
        'priority' => TicketPriority::High,
        'resolution_time_minutes' => 333,
        'active' => true,
    ]);

    Livewire::test(EditTicket::class, ['record' => $ticket->getRouteKey()])
        ->fillForm(['category_id' => $newCategory->id])
        ->call('save')
        ->assertHasNoFormErrors();

    $fresh = $ticket->fresh();
    expect($fresh->category_id)->toBe($newCategory->id)
        ->and($fresh->sla_due_at)->not->toBeNull()
        ->and($fresh->sla_due_at->equalTo($ticket->created_at->copy()->addMinutes(333)))->toBeTrue();
});

test('a ticket.assign holder can change a ticket team from the edit form', function () {
    Permission::findOrCreate('ticket.view-all');
    Permission::findOrCreate('ticket.manage');
    Permission::findOrCreate('ticket.assign');
    $manager = User::factory()->create();
    $manager->givePermissionTo(['ticket.view-all', 'ticket.manage', 'ticket.assign']);
    $this->actingAs($manager, 'web');

    $team = Team::factory()->for($this->department)->create();
    $newTeam = Team::factory()->for($this->department)->create();
    $ticket = Ticket::factory()->create([
        'category_id' => $this->category->id,
        'department_id' => $this->department->id,
        'team_id' => $team->id,
    ]);

    Livewire::test(EditTicket::class, ['record' => $ticket->getRouteKey()])
        ->assertFormFieldEnabled('team_id')
        ->fillForm(['team_id' => $newTeam->id])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($ticket->fresh()->team_id)->toBe($newTeam->id);
});

test('a user without ticket.assign cannot change the team and their edit leaves it unchanged', function () {
    Permission::findOrCreate('ticket.view-all');
    Permission::findOrCreate('ticket.manage');
    $editor = User::factory()->create();
    $editor->givePermissionTo(['ticket.view-all', 'ticket.manage']);
    $this->actingAs($editor, 'web');

    $team = Team::factory()->for($this->department)->create();
    $otherTeam = Team::factory()->for($this->department)->create();
    $ticket = Ticket::factory()->create([
        'category_id' => $this->category->id,
        'department_id' => $this->department->id,
        'team_id' => $team->id,
    ]);

    Livewire::test(EditTicket::class, ['record' => $ticket->getRouteKey()])
        ->assertFormFieldDisabled('team_id')
        // Even with tampered state, team_id is not dehydrated into $data and
        // EditTicket falls back to the record's current team, never null.
        ->fillForm(['team_id' => $otherTeam->id, 'subject' => 'Edited subject'])
        ->call('save')
        ->assertHasNoFormErrors();

    $fresh = $ticket->fresh();
    expect($fresh->subject)->toBe('Edited subject')
        ->and($fresh->team_id)->toBe($team->id);
});

test('a ticket.manage holder cannot edit a closed ticket', function () {
    Permission::findOrCreate('ticket.view-all');
    Permission::findOrCreate('ticket.manage');
    $manager = User::factory()->create();
    $manager->givePermissionTo(['ticket.view-all', 'ticket.manage']);
    $this->actingAs($manager, 'web');

    $closed = Ticket::factory()->closed()->create();

    expect(TicketResource::canEdit($closed))->toBeFalse();
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
