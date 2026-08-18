<?php

use App\Enums\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('the role/permission seeder creates all roles and permissions with the right assignments', function () {
    Artisan::call('db:seed', ['--class' => RolePermissionSeeder::class]);

    expect(Permission::count())->toBe(18)
        ->and(Role::count())->toBe(5);

    $customer = Role::findByName(UserRole::Customer->value);
    expect($customer->permissions->pluck('name')->sort()->values()->all())
        ->toBe(['comment.create', 'comment.delete-own', 'ticket.create', 'ticket.update-own', 'ticket.view-own']);

    $teamLead = Role::findByName(UserRole::TeamLead->value);
    expect($teamLead->permissions->pluck('name')->sort()->values()->all())
        ->toBe(['comment.delete-any', 'ticket.assign', 'ticket.manage']);

    $supportAgent = Role::findByName(UserRole::SupportAgent->value);
    expect($supportAgent->permissions->pluck('name')->sort()->values()->all())
        ->toBe([
            'comment.create',
            'comment.delete-own',
            'ticket.change-priority',
            'ticket.close',
            'ticket.create',
            'ticket.manage',
            'ticket.reopen',
            'ticket.view-own',
            'ticket.view-team',
        ]);

    $supportManager = Role::findByName(UserRole::SupportManager->value);
    expect($supportManager->permissions->pluck('name')->sort()->values()->all())
        ->toBe([
            'comment.create',
            'comment.delete-any',
            'org.manage',
            'sla.manage',
            'ticket.assign',
            'ticket.change-priority',
            'ticket.close',
            'ticket.delete',
            'ticket.manage',
            'ticket.reopen',
            'ticket.view-all',
        ]);

    $administrator = Role::findByName(UserRole::Administrator->value);
    expect($administrator->permissions()->count())->toBe(18);
});
