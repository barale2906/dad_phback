<?php

namespace App\Policies;

use App\Models\Opcion;
use App\Models\User;

class OpcionPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->rol, ['SUPER_ADMIN', 'ADMIN_PH', 'LOGISTICA', 'LECTURA'], true);
    }

    public function view(User $user, Opcion $opcion): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return in_array($user->rol, ['SUPER_ADMIN', 'ADMIN_PH', 'LOGISTICA'], true);
    }

    public function update(User $user, Opcion $opcion): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Opcion $opcion): bool
    {
        return $this->create($user);
    }
}
