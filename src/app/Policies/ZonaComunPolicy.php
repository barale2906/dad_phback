<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ZonaComun;

class ZonaComunPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->rol, ['SUPER_ADMIN', 'ADMIN_PH', 'LOGISTICA', 'LECTURA'], true);
    }

    public function view(User $user, ZonaComun $zonaComun): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return in_array($user->rol, ['SUPER_ADMIN', 'ADMIN_PH', 'LOGISTICA'], true);
    }

    public function update(User $user, ZonaComun $zonaComun): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, ZonaComun $zonaComun): bool
    {
        return $this->create($user);
    }
}
