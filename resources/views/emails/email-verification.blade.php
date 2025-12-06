<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
    <style>
        /* Base styles with CSS variables for themes */
        :root {
            --primary-color: #3B82F6;
            /* Blue */
            --secondary-color: #10B981;
            /* Green */
            --background-color: #FFFFFF;
            --text-color: #1F2937;
            --border-color: #E5E7EB;
            --card-bg: #F9FAFB;
            --footer-bg: #F3F4F6;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --primary-color: #60A5FA;
                --secondary-color: #34D399;
                --background-color: #111827;
                --text-color: #F9FAFB;
                --border-color: #374151;
                --card-bg: #1F2937;
                --footer-bg: #111827;
            }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--background-color);
            padding: 20px;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: var(--background-color);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 40px 20px;
            text-align: center;
            color: white;
        }

        .logo {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
            letter-spacing: 1px;
        }

        .tagline {
            font-size: 14px;
            opacity: 0.9;
        }

        .content {
            padding: 40px 30px;
            background: var(--card-bg);
        }

        .greeting {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--text-color);
        }

        .message {
            font-size: 16px;
            color: var(--text-color);
            opacity: 0.9;
            margin-bottom: 30px;
        }

        .verification-box {
            background: white;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 25px;
            text-align: center;
            margin: 30px 0;
            background: var(--background-color);
        }

        .verification-label {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-color);
            opacity: 0.7;
            margin-bottom: 10px;
        }

        .verification-code {
            font-size: 42px;
            font-weight: 700;
            letter-spacing: 8px;
            color: var(--primary-color);
            margin: 15px 0;
            font-family: 'Courier New', monospace;
            background: var(--card-bg);
            padding: 15px;
            border-radius: 6px;
            border: 2px dashed var(--border-color);
        }

        .expiry-notice {
            font-size: 14px;
            color: var(--text-color);
            opacity: 0.7;
            margin-top: 15px;
        }

        .expiry-highlight {
            color: var(--secondary-color);
            font-weight: 600;
        }

        .security-note {
            background: var(--footer-bg);
            border-left: 4px solid var(--secondary-color);
            padding: 15px;
            margin: 25px 0;
            border-radius: 4px;
            font-size: 14px;
        }

        .action-button {
            display: inline-block;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 8px;
            font-weight: 600;
            margin: 20px 0;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.5);
        }

        .footer {
            background: var(--footer-bg);
            padding: 25px 30px;
            text-align: center;
            border-top: 1px solid var(--border-color);
        }

        .footer-text {
            font-size: 14px;
            color: var(--text-color);
            opacity: 0.7;
            margin-bottom: 10px;
        }

        .social-links {
            margin: 20px 0;
        }

        .social-icon {
            display: inline-block;
            width: 36px;
            height: 36px;
            background: var(--border-color);
            border-radius: 50%;
            margin: 0 8px;
            line-height: 36px;
            text-align: center;
            color: var(--text-color);
            text-decoration: none;
            transition: background 0.3s;
        }

        .social-icon:hover {
            background: var(--primary-color);
            color: white;
        }

        .copyright {
            font-size: 12px;
            color: var(--text-color);
            opacity: 0.5;
            margin-top: 20px;
        }

        .support-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .support-link:hover {
            text-decoration: underline;
        }

        /* Responsive design */
        @media (max-width: 640px) {
            .content {
                padding: 30px 20px;
            }

            .verification-code {
                font-size: 32px;
                letter-spacing: 6px;
                padding: 12px;
            }

            .header {
                padding: 30px 15px;
            }

            .greeting {
                font-size: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="email-container">
        <!-- Header Section -->
        <div class="header">
            <div class="logo">{{ $appName }}</div>
            <div class="tagline">Connect, Share, Inspire</div>
        </div>

        <!-- Main Content -->
        <div class="content">
            <h1 class="greeting">Hello {{ $name ?: 'User' }},</h1>

            <p class="message">
                Thank you for signing up! To complete your registration and verify your email address,
                please use the verification code below:
            </p>

            <!-- Verification Code Box -->
            <div class="verification-box">
                <div class="verification-label">Verification Code</div>
                <div class="verification-code">{{ $code }}</div>
                <div class="expiry-notice">
                    This code will expire in <span class="expiry-highlight">{{ $expiresIn }} minutes</span>
                </div>
            </div>

            <!-- Security Note -->
            <div class="security-note">
                <strong>Security Tip:</strong> This code was requested from your account.
                If you didn't make this request, please ignore this email or contact our support team immediately.
            </div>

            <!-- Action Button (if needed) -->
            <div style="text-align: center;">
                <a href="{{ config('app.url') }}/verify-email" class="action-button">
                    Complete Verification
                </a>
            </div>

            <p class="message" style="margin-top: 30px;">
                Need help? Contact our support team or visit our help center for assistance.
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-text">
                This email was sent to you as part of your {{ $appName }} account registration.
            </div>

            <div class="social-links">
                <a href="#" class="social-icon">F</a>
                <a href="#" class="social-icon">T</a>
                <a href="#" class="social-icon">I</a>
                <a href="#" class="social-icon">L</a>
            </div>

            <div class="copyright">
                © {{ $currentYear }} {{ $appName }}. All rights reserved.<br>
                <a href="{{ config('app.url') }}/privacy" class="support-link">Privacy Policy</a> |
                <a href="{{ config('app.url') }}/terms" class="support-link">Terms of Service</a>
            </div>

            <div class="copyright" style="margin-top: 10px;">
                <a href="mailto:support@wonderwaypictures.com" class="support-link">
                    support@wonderwaypictures.com
                </a>
            </div>
        </div>
    </div>
</body>

</html>