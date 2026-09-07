<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdminResourcePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->checkAccess($user);
    }

    public function view(User $user): bool
    {
        return $this->checkAccess($user);
    }

    public function create(User $user): bool
    {
        return $this->checkAccess($user);
    }

    public function update(User $user): bool
    {
        return $this->checkAccess($user);
    }

    public function delete(User $user): bool
    {
        return $this->checkAccess($user);
    }

    public function restore(User $user): bool
    {
        return $this->checkAccess($user);
    }

    public function forceDelete(User $user): bool
    {
        return $this->checkAccess($user);
    }

    private function checkAccess(User $user): bool
    {
        if (!empty($user->admin_role)) {
            return true;
        }

        return $user->hasAnyRole(['super_admin', 'admin', 'manager', 'viewer']);
    }
}
