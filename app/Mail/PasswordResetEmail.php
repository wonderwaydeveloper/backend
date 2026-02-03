<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetEmail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public $user;
    public $code;

    public function __construct($user, $code)
    {
        // Validate inputs
        if (!$user || !isset($user->email) || !filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid user or email');
        }
        
        if (!$code || !is_string($code) || strlen($code) !== 6 || !ctype_digit($code)) {
            throw new \InvalidArgumentException('Invalid reset code');
        }
        
        $this->user = $user;
        $this->code = $code;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Password Reset - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset',
            with: ['user' => $this->user, 'code' => $this->code],
        );
    }
}
