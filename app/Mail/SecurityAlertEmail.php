<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SecurityAlertEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public $user,
        public $alertData
    ) {}

    public function build()
    {
        return $this->subject('Security Alert - ' . config('app.name'))
                    ->view('emails.security-alert')
                    ->with([
                        'user' => $this->user,
                        'alertData' => $this->alertData
                    ]);
    }
}