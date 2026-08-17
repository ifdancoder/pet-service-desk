<?php

use App\Events\TicketCreated;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketCreatedNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => RolePermissionSeeder::class]);
});

test('notifies the requester and department staff', function () {
    Notification::fake();

    $department = Department::factory()->create();
    $requester = User::factory()->for($department)->create();
    Permission::findOrCreate('ticket.view-team');
    $staffMember = User::factory()->for($department)->create();
    $staffMember->givePermissionTo('ticket.view-team');

    $ticket = Ticket::factory()->create([
        'requester_id' => $requester->id,
        'department_id' => $department->id,
    ]);

    event(new TicketCreated($ticket));

    Notification::assertSentTo($requester, TicketCreatedNotification::class);
    Notification::assertSentTo($staffMember, TicketCreatedNotification::class);
});

test('does not notify department users without staff view permissions', function () {
    Notification::fake();

    $department = Department::factory()->create();
    $requester = User::factory()->for($department)->create();
    Permission::findOrCreate('ticket.view-own');
    $otherCustomer = User::factory()->for($department)->create();
    $otherCustomer->givePermissionTo('ticket.view-own');

    $ticket = Ticket::factory()->create([
        'requester_id' => $requester->id,
        'department_id' => $department->id,
    ]);

    event(new TicketCreated($ticket));

    Notification::assertNotSentTo($otherCustomer, TicketCreatedNotification::class);
});
