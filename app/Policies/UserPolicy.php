<?php

namespace App\Policies;

use App\Models\User;

/**
 * Bloc B — User authorization & IDOR defense.
 *
 * Rules:
 * - A user may only view and update HIS OWN account settings.
 * - Public producer profiles are viewable by all.
 * - An admin may view all users and suspend/reactivate accounts,
 *   but normal users cannot access admin endpoints.
 * - Crucially: a user can NEVER self-assign or elevate to the 'admin' role.
 */
class UserPolicy
{
    public function view(User $user, User $target): bool
    {
        return (int) $user->id === (int) $target->id
            || $target->role === 'producer'
            || $user->role === 'admin';
    }

    public function update(User $user, User $target): bool
    {
        return (int) $user->id === (int) $target->id;
    }

    public function changeRole(User $user, User $target): bool
    {
        // Strict IDOR/Privilege Escalation protection: never allowed via regular user profile updates.
        return false;
    }

    public function suspend(User $user, User $target): bool
    {
        return $user->role === 'admin' && (int) $user->id !== (int) $target->id;
    }

    public function reactivate(User $user, User $target): bool
    {
        return $user->role === 'admin';
    }
}

