<?php

namespace App\Policies;

use App\Models\TicketCategory;
use App\Models\User;

class TicketCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('org.manage');
    }

    public function view(User $user, TicketCategory $ticketCategory): bool
    {
        return $user->can('org.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('org.manage');
    }

    public function update(User $user, TicketCategory $ticketCategory): bool
    {
        return $user->can('org.manage');
    }

    public function delete(User $user, TicketCategory $ticketCategory): bool
    {
        return $user->can('org.manage');
    }
}
