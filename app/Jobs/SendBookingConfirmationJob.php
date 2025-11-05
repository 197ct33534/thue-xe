<?php

namespace App\Jobs;

use App\Mail\BookingConfirmationMail;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendBookingConfirmationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    public function handle()
    {
        Mail::to($this->booking->user->email)->send(new BookingConfirmationMail($this->booking));
    }
}