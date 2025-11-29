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
        public string $name = ''
    ) {}

    public function handle(): void
    {
        try {
            if ($this->type === 'verification') {
                Mail::to($this->email)->send(
                    new EmailVerificationMail($this->code, $this->name)
                );
            } elseif ($this->type === 'password_reset') {
                Mail::to($this->email)->send(
                    new PasswordResetMail($this->code, $this->name)
                );
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send verification email: ' . $e->getMessage());
            // می‌توانید در اینجا notification برای ادمین ارسال کنید
        }
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error('SendVerificationEmail job failed: ' . $exception->getMessage());
    }
}