<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DeviceVerificationEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public $user,
        public $code,
        public $deviceInfo
    ) {}

    public function build()
    {
        return $this->subject('New Device Login - Verification Required')
                    ->view('emails.device-verification')
                    ->with([
                        'user' => $this->user,
                        'code' => $this->code,
                        'deviceInfo' => $this->deviceInfo
                    ]);
    }
}