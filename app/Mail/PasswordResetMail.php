<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $name = ''
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'بازیابی رمز عبور - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset',
            with: [
                'code' => $this->code,
                'name' => $this->name ?: 'کاربر',
                'expiresIn' => 30, // دقیقه
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}