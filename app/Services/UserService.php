<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class UserService
{

    /**
     * Determine if the given user has any descendants.
     *
     * A descendant is defined as a user for whom the given user is set as a supervisor.
     * This method checks if there is at least one user associated as a descendant.
     *
     * @param User|int $user The user object or user ID to check.
     * @return bool True if the user has descendants, false otherwise.
     */
    public static function hasDescendants(User|int $user): bool
    {
        $userId = $user instanceof User ? $user->id : $user;
        return DB::table('user_supervisors')->where('supervisor_id', $userId)->limit(1)->exists();
    }

    /**
     * Get all descendants of a user.
     *
     * A user can have many descendants when they are set as a supervisor of another user.
     * This method will fetch all users that are set as a descendant of the given user.
     *
     * @param User|int $user
     * @param array    $columns
     * @return Collection The collection of descendants.
     */
    public static function getDescendants(User|int $user, array $columns = ['id']): Collection
    {
        $userId = $user instanceof User ? $user->id : $user;
        return User::select($columns)
            ->whereHas('supervisors', fn($q) => $q->where('supervisor_id', $userId))
            ->get();
    }
}
