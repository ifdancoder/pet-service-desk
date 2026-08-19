<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('user.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('user.manage');
    }

    public function view(User $user, User $target): bool
    {
        return $user->can('user.manage') || $user->id === $target->id;
    }

    public function update(User $user, User $target): bool
    {
        return $user->can('user.manage') || $user->id === $target->id;
    }

    public function delete(User $user, User $target): bool
    {
        return $user->can('user.manage') && $user->id !== $target->id;
    }
}
