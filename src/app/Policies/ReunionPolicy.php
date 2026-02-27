<?php

namespace App\Policies;

use App\Models\Reunion;
use App\Models\User;

class ReunionPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->rol, ['SUPER_ADMIN', 'ADMIN_PH', 'LOGISTICA', 'LECTURA'], true);
    }

    public function view(User $user, Reunion $reunion): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return in_array($user->rol, ['SUPER_ADMIN', 'ADMIN_PH', 'LOGISTICA'], true);
    }

    public function update(User $user, Reunion $reunion): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Reunion $reunion): bool
    {
        return $this->create($user);
    }
}
