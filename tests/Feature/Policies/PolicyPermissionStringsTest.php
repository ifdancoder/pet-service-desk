<?php

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;

test('every permission string referenced by a Policy class exists as a seeded permission', function () {
    Artisan::call('db:seed', ['--class' => RolePermissionSeeder::class]);

    $permissionsUsedByPolicies = [
        'ticket.view-own',
        'ticket.view-team',
        'ticket.view-all',
        'ticket.create',
        'ticket.update-own',
        'ticket.assign',
        'ticket.change-priority',
        'ticket.close',
        'ticket.reopen',
        'ticket.delete',
        'comment.create',
        'comment.delete-own',
        'comment.delete-any',
        'sla.manage',
        'user.manage',
    ];

    foreach ($permissionsUsedByPolicies as $permission) {
        expect(Permission::where('name', $permission)->exists())
            ->toBeTrue("Policy references permission '{$permission}' but it is not seeded by RolePermissionSeeder.");
    }
});
