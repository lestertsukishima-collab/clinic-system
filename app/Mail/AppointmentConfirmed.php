<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public $appointment;

    public function __construct(?Appointment $appointment = null)
    {
        $this->appointment = $appointment;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Appointment Confirmed',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'appointments.appointment_confirmed',
            with: [
                'appointment' => $this->appointment,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
