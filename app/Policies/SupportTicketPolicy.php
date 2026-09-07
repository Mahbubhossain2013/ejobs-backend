<?php

namespace App\Policies;

use App\Models\User;
use App\Models\SupportTicket;
use Illuminate\Auth\Access\HandlesAuthorization;

class SupportTicketPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any support tickets.
     * ScopeTickets() method must be called to filter results.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Scope support tickets to only the user's own tickets (for non-admins).
     */
    public function scopeTickets($query, User $user)
    {
        if (!empty($user->admin_role) || $user->hasAnyRole(['super_admin', 'admin'])) {
            return $query;
        }
        return $query->where('user_id', $user->id);
    }

    /**
     * Determine whether the user can view the specific support ticket.
     */
    public function view(User $user, SupportTicket $ticket): bool
    {
        return !empty($user->admin_role) || $user->hasAnyRole(['super_admin', 'admin']) || $ticket->user_id === $user->id;
    }

    /**
     * Determine whether the user can create support tickets.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the support ticket.
     */
    public function update(User $user, SupportTicket $ticket): bool
    {
        return !empty($user->admin_role) || $user->hasAnyRole(['super_admin', 'admin']) || $ticket->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the support ticket.
     */
    public function delete(User $user, SupportTicket $ticket): bool
    {
        return !empty($user->admin_role) || $user->hasAnyRole(['super_admin', 'admin']);
    }
}
