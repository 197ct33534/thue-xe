<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function view(User $user, Booking $booking)
    {
        return $user->is_admin || $booking->user_id === $user->id;
    }

    public function update(User $user, Booking $booking)
    {
        return $user->is_admin;
    }
}