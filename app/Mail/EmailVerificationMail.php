<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * کد تأیید
     */
    public string $code;

    /**
     * نام کاربر
     */
    public string $name;

    /**
     * زمان انقضا (دقیقه)
     */
    public int $expiresIn;

    /**
     * Create a new message instance.
     */
    public function __construct(string $code, string $name = '', int $expiresIn = 30)
    {
        $this->code = $code;
        $this->name = $name;
        $this->expiresIn = $expiresIn;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Email Verification Code',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.email-verification',
            with: [
                'code' => $this->code,
                'name' => $this->name,
                'expiresIn' => $this->expiresIn,
                'currentYear' => date('Y'),
                'appName' => config('app.name', 'Wonder Way Pictures'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}