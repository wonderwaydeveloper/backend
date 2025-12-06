<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
    <style>
        /* Base styles with CSS variables for themes */
        :root {
            --primary-color: #10B981;
            /* Green */
            --secondary-color: #3B82F6;
            /* Blue */
            --warning-color: #F59E0B;
            --background-color: #FFFFFF;
            --text-color: #1F2937;
            --border-color: #E5E7EB;
            --card-bg: #F9FAFB;
            --footer-bg: #F3F4F6;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --primary-color: #34D399;
                --secondary-color: #60A5FA;
                --warning-color: #FBBF24;
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
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="white" fill-opacity="0.1"/></svg>');
            background-size: cover;
        }

        .logo {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
            letter-spacing: 1px;
            position: relative;
            z-index: 1;
        }

        .tagline {
            font-size: 14px;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .icon {
            font-size: 48px;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
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
            margin-bottom: 25px;
        }

        .reset-box {
            background: var(--background-color);
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
            position: relative;
            overflow: hidden;
        }

        .reset-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
        }

        .reset-label {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--text-color);
            opacity: 0.8;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .reset-code {
            font-size: 44px;
            font-weight: 800;
            letter-spacing: 10px;
            color: var(--primary-color);
            margin: 20px 0;
            font-family: 'Courier New', monospace;
            background: var(--card-bg);
            padding: 20px;
            border-radius: 8px;
            border: 2px solid var(--border-color);
            display: inline-block;
            min-width: 300px;
        }

        .timer {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: var(--warning-color);
            color: white;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-top: 15px;
        }

        .timer-icon {
            font-size: 16px;
        }

        .instructions {
            background: var(--footer-bg);
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
            border-left: 4px solid var(--secondary-color);
        }

        .instructions-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--text-color);
        }

        .instructions-list {
            list-style: none;
            padding-left: 0;
        }

        .instructions-list li {
            padding: 8px 0;
            padding-left: 28px;
            position: relative;
            font-size: 14px;
        }

        .instructions-list li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--primary-color);
            font-weight: bold;
        }

        .action-button {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            text-decoration: none;
            padding: 16px 40px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            margin: 25px 0;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
            border: none;
            cursor: pointer;
        }

        .action-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        }

        .warning-note {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid var(--warning-color);
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
            text-align: center;
        }

        .warning-title {
            color: var(--warning-color);
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .footer {
            background: var(--footer-bg);
            padding: 30px;
            text-align: center;
            border-top: 1px solid var(--border-color);
        }

        .footer-text {
            font-size: 14px;
            color: var(--text-color);
            opacity: 0.7;
            margin-bottom: 15px;
            line-height: 1.8;
        }

        .contact-info {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 25px 0;
            flex-wrap: wrap;
        }

        .contact-item {
            text-align: center;
            min-width: 120px;
        }

        .contact-icon {
            font-size: 20px;
            color: var(--primary-color);
            margin-bottom: 8px;
        }

        .contact-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.7;
        }

        .contact-value {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-color);
        }

        .copyright {
            font-size: 12px;
            color: var(--text-color);
            opacity: 0.5;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }

        .support-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .support-link:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }

        /* Responsive design */
        @media (max-width: 640px) {
            .content {
                padding: 30px 20px;
            }

            .reset-code {
                font-size: 32px;
                letter-spacing: 6px;
                padding: 15px;
                min-width: auto;
                width: 100%;
            }

            .header {
                padding: 30px 15px;
            }

            .greeting {
                font-size: 20px;
            }

            .contact-info {
                flex-direction: column;
                gap: 20px;
            }

            .action-button {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>

<body>
    <div class="email-container">
        <!-- Header Section -->
        <div class="header">
            <div class="icon">🔐</div>
            <div class="logo">{{ $appName }}</div>
            <div class="tagline">Password Reset Request</div>
        </div>

        <!-- Main Content -->
        <div class="content">
            <h1 class="greeting">Hello {{ $name ?: 'User' }},</h1>

            <p class="message">
                We received a request to reset your password. Use the verification code below
                to securely reset your account password:
            </p>

            <!-- Password Reset Box -->
            <div class="reset-box">
                <div class="reset-label">Reset Code</div>
                <div class="reset-code">{{ $code }}</div>

                <div class="timer">
                    <span class="timer-icon">⏰</span>
                    <span>Expires in {{ $expiresIn }} minutes</span>
                </div>
            </div>

            <!-- Instructions -->
            <div class="instructions">
                <div class="instructions-title">How to reset your password:</div>
                <ul class="instructions-list">
                    <li>Enter the code above on the password reset page</li>
                    <li>Create a new strong password (min. 8 characters)</li>
                    <li>Use a combination of letters, numbers, and symbols</li>
                    <li>Confirm your new password</li>
                </ul>
            </div>

            <!-- Action Button -->
            <div style="text-align: center;">
                <a href="{{ config('app.url') }}/reset-password" class="action-button">
                    Reset Password Now
                </a>
            </div>

            <!-- Warning Note -->
            <div class="warning-note">
                <div class="warning-title">⚠️ Security Alert</div>
                <p style="font-size: 14px; margin: 0;">
                    If you didn't request this password reset, please ignore this email or
                    <a href="{{ config('app.url') }}/security" class="support-link">secure your account</a>
                    immediately. Your account security is important to us.
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-text">
                For your security, this code will expire in {{ $expiresIn }} minutes.<br>
                Never share your verification code with anyone.
            </div>

            <div class="contact-info">
                <div class="contact-item">
                    <div class="contact-icon">📧</div>
                    <div class="contact-label">Email</div>
                    <div class="contact-value">support@wonderwaypictures.com</div>
                </div>

                <div class="contact-item">
                    <div class="contact-icon">🌐</div>
                    <div class="contact-label">Website</div>
                    <div class="contact-value">wonderwaypictures.com</div>
                </div>

                <div class="contact-item">
                    <div class="contact-icon">⏰</div>
                    <div class="contact-label">Support Hours</div>
                    <div class="contact-value">24/7</div>
                </div>
            </div>

            <div class="copyright">
                © {{ $currentYear }} {{ $appName }}. All rights reserved.<br>
                <a href="{{ config('app.url') }}/security" class="support-link">Security Center</a> •
                <a href="{{ config('app.url') }}/help" class="support-link">Help Center</a> •
                <a href="{{ config('app.url') }}/unsubscribe" class="support-link">Email Preferences</a>
            </div>
        </div>
    </div>
</body>

</html>