<?php

namespace App\Policies;

use App\Models\User;

/**
 * User management is admin-only (UserResource::canAccess already gates the UI,
 * this enforces it at the Gate layer too). Without a registered policy,
 * `authorizeIndividualRecords('delete')` on the users bulk action hits the raw
 * Gate, which denies when no policy exists — silently breaking bulk delete.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $record): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $record): bool
    {
        return $user->isAdmin();
    }

    /**
     * Admins may delete any user except themselves — policy-level backing for
     * the self-delete guards on the row, header, and bulk actions.
     */
    public function delete(User $user, User $record): bool
    {
        return $user->isAdmin() && ! $user->is($record);
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
