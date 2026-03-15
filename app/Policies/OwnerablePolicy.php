<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class OwnerablePolicy
{
    /**
     * @param \App\Models\User $user
     *
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * @param \App\Models\User                    $user
     * @param \Illuminate\Database\Eloquent\Model $record
     *
     * @return bool
     */
    public function view(User $user, Model $record): bool
    {
        return true;
    }

    /**
     * @param \App\Models\User $user
     *
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isEditor();
    }

    /**
     * @param \App\Models\User                    $user
     * @param \Illuminate\Database\Eloquent\Model $record
     *
     * @return bool
     */
    public function update(User $user, Model $record): bool
    {
        return $user->isAdmin() || $record->user_id === $user->id;
    }

    /**
     * @param \App\Models\User                    $user
     * @param \Illuminate\Database\Eloquent\Model $record
     *
     * @return bool
     */
    public function delete(User $user, Model $record): bool
    {
        return $user->isAdmin() || $record->user_id === $user->id;
    }

    /**
     * @param \App\Models\User                    $user
     * @param \Illuminate\Database\Eloquent\Model $record
     *
     * @return bool
     */
    public function restore(User $user, Model $record): bool
    {
        return $user->isAdmin() || $record->user_id === $user->id;
    }

    /**
     * @param \App\Models\User                    $user
     * @param \Illuminate\Database\Eloquent\Model $record
     *
     * @return bool
     */
    public function forceDelete(User $user, Model $record): bool
    {
        return $user->isAdmin();
    }
}
