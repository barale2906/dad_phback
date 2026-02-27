<?php

namespace App\Policies;

use App\Models\Convocatoria;
use App\Models\User;

class ConvocatoriaPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->rol, ['SUPER_ADMIN', 'ADMIN_PH', 'LOGISTICA', 'LECTURA'], true);
    }

    public function view(User $user, Convocatoria $convocatoria): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return in_array($user->rol, ['SUPER_ADMIN', 'ADMIN_PH', 'LOGISTICA'], true);
    }

    public function update(User $user, Convocatoria $convocatoria): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Convocatoria $convocatoria): bool
    {
        return $this->create($user);
    }
}
