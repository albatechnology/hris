<?php

namespace App\Policies;

use App\Models\GuestBook;
use App\Models\User;

class GuestBookPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
        // return $user->can('guest_book_read');
    }

    public function view(User $user, GuestBook $guestBook): bool
    {
        return true;
        // return $user->can('guest_book_read');
    }

    public function create(User $user): bool
    {
        return true;
        // return $user->can('guest_book_create');
    }

    public function update(User $user, GuestBook $guestBook): bool
    {
        return true;
        // return $user->can('guest_book_edit');
    }

    public function delete(User $user, GuestBook $guestBook): bool
    {
        return true;
        // return $user->can('guest_book_delete');
    }

    public function restore(User $user, GuestBook $guestBook): bool
    {
        return true;
        // return $user->can('guest_book_access');
    }

    public function forceDelete(User $user, GuestBook $guestBook): bool
    {
        return true;
        // return $user->can('guest_book_delete');
    }
}