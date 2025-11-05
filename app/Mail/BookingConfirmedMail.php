<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

class BookingConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;

    public function __construct(Booking $booking)
    {
        $this->booking = $booking->load('details.vehicle', 'user');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Xác nhận đặt cọc thành công - ' . $this->booking->booking_code,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.booking.confirmed',
            with: ['booking' => $this->booking]
        );
    }

    public function attachments(): array
    {
        $pdf = Pdf::loadView('invoices.booking', ['booking' => $this->booking])
                  ->setPaper('a4', 'portrait');

        return [
            Attachment::fromData(fn () => $pdf->output(), "hoa-don-{$this->booking->booking_code}.pdf")
                      ->withMime('application/pdf'),
        ];
    }
}