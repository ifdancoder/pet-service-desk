<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    private const PERMISSIONS = [
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
        'role.manage',
        'org.manage',
        'ticket.manage',
    ];

    /**
     * @var array<string, array<int, string>>
     */
    private const ROLE_PERMISSIONS = [
        'customer' => [
            'ticket.view-own',
            'ticket.create',
            'ticket.update-own',
            'comment.create',
            'comment.delete-own',
        ],
        'support_agent' => [
            'ticket.view-own',
            'ticket.view-team',
            'ticket.create',
            'ticket.change-priority',
            'ticket.close',
            'ticket.reopen',
            'ticket.manage',
            'comment.create',
            'comment.delete-own',
        ],
        'team_lead' => [
            'ticket.assign',
            'ticket.manage',
            'comment.delete-any',
        ],
        'support_manager' => [
            'ticket.view-all',
            'ticket.assign',
            'ticket.change-priority',
            'ticket.close',
            'ticket.reopen',
            'ticket.delete',
            'ticket.manage',
            'comment.create',
            'comment.delete-any',
            'sla.manage',
            'org.manage',
        ],
        'administrator' => [
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
            'ticket.manage',
            'comment.create',
            'comment.delete-own',
            'comment.delete-any',
            'sla.manage',
            'user.manage',
            'role.manage',
            'org.manage',
        ],
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission);
        }

        foreach (UserRole::cases() as $userRole) {
            $role = Role::findOrCreate($userRole->value);
            $role->syncPermissions(self::ROLE_PERMISSIONS[$userRole->value]);
        }
    }
}
