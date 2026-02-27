<?php

namespace App\Policies;

use App\Models\Ph;
use App\Models\User;

class PhPolicy
{
    public function view(User $user, Ph $ph): bool
    {
        return in_array($user->rol, ['SUPER_ADMIN', 'ADMIN_PH', 'LOGISTICA', 'LECTURA'], true);
    }

    public function update(User $user, Ph $ph): bool
    {
        return in_array($user->rol, ['SUPER_ADMIN', 'ADMIN_PH'], true);
    }
}
