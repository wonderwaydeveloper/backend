<?php

namespace App\Console\Commands;

use App\Mail\BulkEmail;
use App\Mail\NotificationEmail;
use App\Mail\PasswordResetEmail;
use App\Mail\VerificationEmail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmailTemplates extends Command
{
    protected $signature = 'email:test {--template=all : Specific template to test (all, verification, password-reset, notification, bulk)}';
    protected $description = 'Test all email templates with sample data';

    public function handle()
    {
        $template = $this->option('template');
        
        $this->info('🧪 Testing Email Templates with Mailtrap...');
        $this->newLine();

        // Create or get test user
        $testUser = $this->getTestUser();
        
        switch ($template) {
            case 'verification':
                $this->testVerificationEmail($testUser);
                break;
            case 'password-reset':
                $this->testPasswordResetEmail($testUser);
                break;
            case 'notification':
                $this->testNotificationEmail($testUser);
                break;
            case 'bulk':
                $this->testBulkEmail($testUser);
                break;
            case 'all':
            default:
                $this->testAllEmails($testUser);
                break;
        }

        $this->newLine();
        $this->info('✅ Email testing completed! Check your Mailtrap inbox.');
    }

    private function testAllEmails($user)
    {
        $this->testVerificationEmail($user);
        $this->line('⏳ Waiting ' . config('performance.email.rate_limit_delay_seconds') . ' seconds for Mailtrap rate limit...');
        sleep(config('performance.email.rate_limit_delay_seconds'));
        
        $this->testPasswordResetEmail($user);
        $this->line('⏳ Waiting ' . config('performance.email.rate_limit_delay_seconds') . ' seconds for Mailtrap rate limit...');
        sleep(config('performance.email.rate_limit_delay_seconds'));
        
        $this->testNotificationEmail($user);
        $this->line('⏳ Waiting ' . config('performance.email.rate_limit_delay_seconds') . ' seconds for Mailtrap rate limit...');
        sleep(config('performance.email.rate_limit_delay_seconds'));
        
        $this->testBulkEmail($user);
    }

    private function testVerificationEmail($user)
    {
        $this->info('📧 Testing Email Verification...');
        
        try {
            $verificationCode = '123456';
            Mail::to($user->email)->send(new VerificationEmail($user, $verificationCode));
            $this->line('  ✓ Verification email sent successfully');
        } catch (\Exception $e) {
            $this->error('  ❌ Verification email failed: ' . $e->getMessage());
        }
    }

    private function testPasswordResetEmail($user)
    {
        $this->info('📧 Testing Password Reset Email...');
        
        try {
            $resetCode = 'ABC123';
            Mail::to($user->email)->send(new PasswordResetEmail($user, $resetCode));
            $this->line('  ✓ Password reset email sent successfully');
        } catch (\Exception $e) {
            $this->error('  ❌ Password reset email failed: ' . $e->getMessage());
        }
    }

    private function testNotificationEmail($user)
    {
        $this->info('📧 Testing Notification Email...');
        
        try {
            $notification = (object) [
                'title' => 'New Like on Your Post',
                'message' => 'John Doe liked your post about Laravel development.',
                'action_text' => 'View Post',
                'action_url' => url('/posts/123'),
            ];
            
            Mail::to($user->email)->send(new NotificationEmail($user, $notification));
            $this->line('  ✓ Notification email sent successfully');
        } catch (\Exception $e) {
            $this->error('  ❌ Notification email failed: ' . $e->getMessage());
        }
    }

    private function testBulkEmail($user)
    {
        $this->info('📧 Testing Bulk Email...');
        
        try {
            $subject = 'Weekly Newsletter - Clevlance Updates';
            $view = 'emails.bulk';
            $data = [
                'content' => 'Check out the latest features and trending posts on our platform!',
                'action_text' => 'Explore Now',
                'action_url' => url('/trending'),
            ];
            
            Mail::to($user->email)->send(new BulkEmail($user, $subject, $view, $data));
            $this->line('  ✓ Bulk email sent successfully');
        } catch (\Exception $e) {
            $this->error('  ❌ Bulk email failed: ' . $e->getMessage());
        }
    }

    private function getTestUser()
    {
        // Try to find existing test user or create one
        $testUser = User::where('email', 'test@mailtrap.io')->first();
        
        if (!$testUser) {
            $testUser = User::create([
                'name' => 'Test User',
                'username' => 'testuser_' . time(),
                'email' => 'test@mailtrap.io',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);
            
            $this->line('📝 Created test user: test@mailtrap.io');
        } else {
            $this->line('📝 Using existing test user: test@mailtrap.io');
        }
        
        return $testUser;
    }
}