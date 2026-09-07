<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Promotion;
use Illuminate\Auth\Access\HandlesAuthorization;

class PromotionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return !empty($user->admin_role) || $user->hasAnyRole(['super_admin', 'admin']);
    }

    public function view(User $user, Promotion $promo): bool
    {
        return !empty($user->admin_role) || $user->hasAnyRole(['super_admin', 'admin']) || $user->id === $promo->user_id;
    }

    public function create(User $user): bool
    {
        return !empty($user->admin_role) || $user->hasAnyRole(['super_admin', 'admin']) || $user->hasRole('employer');
    }

    public function update(User $user, Promotion $promo): bool
    {
        return !empty($user->admin_role) || $user->hasAnyRole(['super_admin', 'admin']) || ($user->id === $promo->user_id && $promo->status !== 'completed');
    }

    public function delete(User $user, Promotion $promo): bool
    {
        return !empty($user->admin_role) || $user->hasAnyRole(['super_admin', 'admin']);
    }
}
