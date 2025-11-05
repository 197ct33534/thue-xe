<?php

namespace App\Listeners;

use App\Events\BookingCreated;
use App\Jobs\SendBookingConfirmationJob;

class SendBookingConfirmation
{
    public function handle(BookingCreated $event)
    {
        SendBookingConfirmationJob::dispatch($event->booking);
    }
}