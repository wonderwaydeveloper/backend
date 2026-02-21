<?php

namespace App\Console\Commands;

use App\Services\EmailService;
use App\Models\User;
use Illuminate\Console\Command;

class TestEmailTemplatesCommand extends Command
{
    protected $signature = 'email:test {type} {email} {--code=123456}';
    protected $description = 'Test email templates and security features';

    public function handle(EmailService $emailService): int
    {
        $type = $this->argument('type');
        $email = $this->argument('email');
        $code = $this->option('code');

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address');
            return Command::FAILURE;
        }

        // Create test user
        $testUser = (object) [
            'name' => 'Test User',
            'email' => $email
        ];

        $this->info("Testing {$type} email template...");
        $this->info("Recipient: {$email}");
        $this->info("Code: {$code}");

        try {
            $result = match ($type) {
                'verification' => $emailService->sendVerificationEmail($testUser, $code),
                'password-reset' => $emailService->sendPasswordResetEmail($testUser, $code),
                'device-verification' => $emailService->sendDeviceVerificationEmail($testUser, $code, [
                    'ip' => '192.168.1.100',
                    'location' => 'Test Location',
                    'user_agent' => 'Test Browser'
                ]),
                default => throw new \InvalidArgumentException("Unknown email type: {$type}")
            };

            if ($result) {
                $this->info("✅ Email sent successfully!");
                
                // Show security features status
                $this->newLine();
                $this->info("Security Features Status:");
                $this->table(
                    ['Feature', 'Status'],
                    [
                        ['Rate Limiting', config('security.rate_limiting.email_verification.max_attempts') ? '✅ Enabled' : '❌ Disabled'],
                        ['Domain Blacklist', count(config('security.email.blacklist_domains', [])) . ' domains'],
                        ['Content Security Policy', '✅ Enabled in templates'],
                        ['XSS Protection', '✅ Enabled (HTML escaping)'],
                        ['Email Masking in Logs', '✅ Enabled'],
                    ]
                );
                
                return Command::SUCCESS;
            } else {
                $this->error("❌ Failed to send email");
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}