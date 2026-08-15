<?php

use App\Enums\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('the role/permission seeder creates all roles and permissions with the right assignments', function () {
    Artisan::call('db:seed', ['--class' => RolePermissionSeeder::class]);

    expect(Permission::count())->toBe(16)
        ->and(Role::count())->toBe(5);

    $customer = Role::findByName(UserRole::Customer->value);
    expect($customer->permissions->pluck('name')->sort()->values()->all())
        ->toBe(['comment.create', 'comment.delete-own', 'ticket.create', 'ticket.view-own']);

    $teamLead = Role::findByName(UserRole::TeamLead->value);
    expect($teamLead->permissions->pluck('name')->sort()->values()->all())
        ->toBe(['comment.delete-any', 'ticket.assign']);

    $administrator = Role::findByName(UserRole::Administrator->value);
    expect($administrator->permissions()->count())->toBe(16);
});
