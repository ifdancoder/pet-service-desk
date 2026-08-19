<?php

use App\Enums\UserRole;
use App\Models\User;
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
        ->toBe([
            'comment.create',
            'comment.delete-any',
            'ticket.assign',
            'ticket.manage',
            'ticket.view-team',
        ]);

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

test('seeded roles and permissions are pinned to the sanctum guard even when the default guard has been switched', function () {
    // actingAs($user, 'web') calls AuthManager::shouldUse('web'), which mutates
    // config('auth.defaults.guard') for the rest of the request. This is exactly
    // the drift that once produced a stray guard_name='web' permission row.
    $this->actingAs(User::factory()->create(), 'web');
    expect(config('auth.defaults.guard'))->toBe('web');

    Artisan::call('db:seed', ['--class' => RolePermissionSeeder::class]);

    expect(User::GUARD_NAME)->toBe('sanctum')
        ->and(Permission::query()->pluck('guard_name')->unique()->all())->toBe(['sanctum'])
        ->and(Role::query()->pluck('guard_name')->unique()->all())->toBe(['sanctum'])
        ->and(Permission::count())->toBe(18)
        ->and(Role::count())->toBe(5);
});
