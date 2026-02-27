<?php

namespace App\Policies;

use App\Models\Asistente;
use App\Models\User;

class AsistentePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->rol, ['SUPER_ADMIN', 'ADMIN_PH', 'LOGISTICA', 'LECTURA'], true);
    }

    public function view(User $user, Asistente $asistente): bool
    {
        return in_array($user->rol, ['SUPER_ADMIN', 'ADMIN_PH', 'LOGISTICA', 'LECTURA'], true);
    }

    public function create(User $user): bool
    {
        return in_array($user->rol, ['SUPER_ADMIN', 'ADMIN_PH', 'LOGISTICA'], true);
    }

    public function update(User $user, Asistente $asistente): bool
    {
        return in_array($user->rol, ['SUPER_ADMIN', 'ADMIN_PH', 'LOGISTICA'], true);
    }

    public function delete(User $user, Asistente $asistente): bool
    {
        return in_array($user->rol, ['SUPER_ADMIN', 'ADMIN_PH', 'LOGISTICA'], true);
    }
}
