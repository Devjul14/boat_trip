<?php

namespace App\Policies;

use App\Models\User;
use App\Models\TripType;
use Illuminate\Auth\Access\HandlesAuthorization;

class TripTypePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_trip::type');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TripType $tripType): bool
    {
        return $user->can('view_trip::type');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_trip::type');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TripType $tripType): bool
    {
        return $user->can('update_trip::type');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TripType $tripType): bool
    {
        return $user->can('delete_trip::type');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_trip::type');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, TripType $tripType): bool
    {
        return $user->can('force_delete_trip::type');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_trip::type');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, TripType $tripType): bool
    {
        return $user->can('restore_trip::type');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_trip::type');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, TripType $tripType): bool
    {
        return $user->can('replicate_trip::type');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_trip::type');
    }
}
