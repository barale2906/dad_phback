<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->rol, ['SUPER_ADMIN', 'ADMIN_PH'], true);
    }

    public function view(User $user, User $target): bool
    {
        return $user->id === $target->id || in_array($user->rol, ['SUPER_ADMIN', 'ADMIN_PH'], true);
    }

    public function create(User $user): bool
    {
        return $user->rol === 'SUPER_ADMIN';
    }

    public function update(User $user, User $target): bool
    {
        return $user->rol === 'SUPER_ADMIN' || $user->id === $target->id;
    }

    public function delete(User $user, User $target): bool
    {
        return $user->rol === 'SUPER_ADMIN' && $user->id !== $target->id;
    }
}
