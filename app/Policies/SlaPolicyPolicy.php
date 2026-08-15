<?php

namespace App\Policies;

use App\Models\SlaPolicy;
use App\Models\User;

class SlaPolicyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sla.manage');
    }

    public function view(User $user, SlaPolicy $slaPolicy): bool
    {
        return $user->can('sla.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('sla.manage');
    }

    public function update(User $user, SlaPolicy $slaPolicy): bool
    {
        return $user->can('sla.manage');
    }

    public function delete(User $user, SlaPolicy $slaPolicy): bool
    {
        return $user->can('sla.manage');
    }
}
