<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Auth\Access\HandlesAuthorization;

class WalletTransactionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return !empty($user->admin_role) || $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function view(User $user, WalletTransaction $tx): bool
    {
        return !empty($user->admin_role) || $user->hasAnyRole(['super_admin', 'admin']) || $user->id === $tx->wallet?->user_id;
    }

    public function create(User $user): bool
    {
        return !empty($user->admin_role) || $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function update(User $user, WalletTransaction $tx): bool
    {
        return !empty($user->admin_role) || $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function delete(User $user, WalletTransaction $tx): bool
    {
        return !empty($user->admin_role) || $user->hasAnyRole(['super_admin', 'admin']);
    }
}
