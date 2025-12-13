<?php

namespace App\Jobs;

use App\Mail\EmailVerificationMail;
use App\Mail\PasswordResetMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendVerificationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $email,
        public string $code,
        public string $type, // 'verification' or 'password_reset'
        public string $name = '',
        public int $expiresIn = 30 // اضافه شد
    ) {}

    public function handle(): void
    {
        try {
            if ($this->type === 'verification') {
                Mail::to($this->email)->send(
                    new EmailVerificationMail($this->code, $this->name, $this->expiresIn)
                );
            } elseif ($this->type === 'password_reset') {
                Mail::to($this->email)->send(
                    new PasswordResetMail($this->code, $this->name, $this->expiresIn)
                );
            }
            
            \Log::info('Email sent successfully', [
                'email' => $this->email,
                'type' => $this->type,
                'expires_in' => $this->expiresIn
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send verification email: ' . $e->getMessage(), [
                'email' => $this->email,
                'type' => $this->type
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error('SendVerificationEmail job failed: ' . $exception->getMessage());
    }
}