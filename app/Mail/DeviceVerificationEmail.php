<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DeviceVerificationEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $code;
    public $deviceInfo;

    public function __construct($user, $code, $deviceInfo)
    {
        $this->user = $user;
        $this->code = $code;
        $this->deviceInfo = $deviceInfo;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Device Login - Verification Required',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.device-verification',
            with: [
                'user' => $this->user,
                'code' => $this->code,
                'deviceInfo' => $this->deviceInfo
            ],
        );
    }
}