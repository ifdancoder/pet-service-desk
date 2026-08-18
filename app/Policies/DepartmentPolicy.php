<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('org.manage');
    }

    public function view(User $user, Department $department): bool
    {
        return $user->can('org.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('org.manage');
    }

    public function update(User $user, Department $department): bool
    {
        return $user->can('org.manage');
    }

    public function delete(User $user, Department $department): bool
    {
        return $user->can('org.manage');
    }
}
