<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <title>Password Reset - {{ $appName }}</title>
    <style>
        /* CSS Variables for Theming */
        :root {
            /* Light Theme */
            --primary-color: #DC2626;
            --primary-light: #EF4444;
            --primary-dark: #B91C1C;
            --secondary-color: #10B981;
            --accent-color: #F59E0B;
            --background-color: #FFFFFF;
            --card-bg: #F8FAFC;
            --text-primary: #1E293B;
            --text-secondary: #64748B;
            --border-color: #E2E8F0;
            --shadow-color: rgba(0, 0, 0, 0.1);
            --success-color: #10B981;
            --warning-color: #F59E0B;
            --error-color: #EF4444;
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 16px;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                /* Dark Theme */
                --primary-color: #EF4444;
                --primary-light: #F87171;
                --primary-dark: #DC2626;
                --secondary-color: #34D399;
                --accent-color: #FBBF24;
                --background-color: #0F172A;
                --card-bg: #1E293B;
                --text-primary: #F1F5F9;
                --text-secondary: #94A3B8;
                --border-color: #334155;
                --shadow-color: rgba(0, 0, 0, 0.3);
                --success-color: #34D399;
                --warning-color: #FBBF24;
                --error-color: #F87171;
            }
        }

        /* Reset & Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: var(--text-primary);
            background-color: var(--background-color);
            padding: 20px;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Email Container */
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: var(--background-color);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: 0 4px 20px var(--shadow-color);
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            padding: 40px 20px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .header::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 70% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
        }

        .header-icon {
            font-size: 48px;
            margin-bottom: 16px;
            position: relative;
            z-index: 1;
        }

        .logo {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
            position: relative;
            z-index: 1;
        }

        .subtitle {
            font-size: 15px;
            opacity: 0.9;
            font-weight: 400;
            position: relative;
            z-index: 1;
        }

        /* Content Area */
        .content {
            padding: 40px 32px;
            background-color: var(--card-bg);
        }

        /* Greeting */
        .greeting {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 20px;
            line-height: 1.3;
        }

        .user-name {
            color: var(--primary-color);
        }

        /* Message */
        .message {
            font-size: 16px;
            color: var(--text-secondary);
            margin-bottom: 32px;
            line-height: 1.7;
        }

        /* Code Box */
        .code-container {
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-color) 100%);
            border-radius: var(--radius-md);
            padding: 32px;
            text-align: center;
            margin: 32px 0;
            position: relative;
            overflow: hidden;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .code-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.15) 50%, transparent 70%);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .code-label {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 12px;
            font-weight: 600;
        }

        .code {
            font-size: 48px;
            font-weight: 700;
            color: white;
            letter-spacing: 8px;
            font-family: 'SF Mono', 'Monaco', 'Courier New', monospace;
            margin: 16px 0;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .timer {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            color: white;
            padding: 10px 18px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-top: 16px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        /* Action Button */
        .action-container {
            text-align: center;
            margin: 32px 0;
        }

        .action-button {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            text-decoration: none;
            padding: 16px 40px;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.3);
        }

        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 38, 38, 0.4);
        }

        /* Instructions */
        .instructions {
            background-color: rgba(59, 130, 246, 0.1);
            border-left: 4px solid #3B82F6;
            padding: 24px;
            border-radius: var(--radius-sm);
            margin: 32px 0;
        }

        .instructions-title {
            font-size: 16px;
            font-weight: 600;
            color: #3B82F6;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .steps {
            list-style: none;
            padding-left: 0;
        }

        .step {
            padding: 8px 0;
            padding-left: 28px;
            position: relative;
            color: var(--text-secondary);
        }

        .step::before {
            content: '→';
            position: absolute;
            left: 0;
            color: #3B82F6;
            font-weight: bold;
            font-size: 14px;
        }

        /* Warning Box */
        .warning-box {
            background-color: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: var(--radius-sm);
            padding: 20px;
            margin: 32px 0;
        }

        .warning-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--warning-color);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Password Tips */
        .tips-box {
            background-color: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: var(--radius-sm);
            padding: 20px;
            margin: 32px 0;
        }

        .tips-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--success-color);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tips-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin-top: 16px;
        }

        .tip-item {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            font-size: 14px;
            color: var(--text-secondary);
        }

        /* Footer */
        .footer {
            background-color: var(--card-bg);
            padding: 32px;
            text-align: center;
            border-top: 1px solid var(--border-color);
        }

        .footer-text {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 24px;
            line-height: 1.6;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 24px;
            margin: 32px 0;
            text-align: center;
        }

        .contact-item {
            padding: 16px;
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-color);
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
            color: var(--text-secondary);
            margin-bottom: 4px;
        }

        .contact-value {
            font-size: 14px;
            font-weight: 500;
            color: var(--text-primary);
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 24px;
            margin: 24px 0;
            flex-wrap: wrap;
        }

        .footer-link {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.2s;
            font-weight: 500;
        }

        .footer-link:hover {
            color: var(--primary-light);
            text-decoration: underline;
        }

        .copyright {
            font-size: 12px;
            color: var(--text-secondary);
            opacity: 0.7;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid var(--border-color);
        }

        /* Responsive Design */
        @media (max-width: 640px) {
            body {
                padding: 16px;
            }

            .header {
                padding: 32px 16px;
            }

            .logo {
                font-size: 24px;
            }

            .content {
                padding: 32px 20px;
            }

            .greeting {
                font-size: 20px;
            }

            .code {
                font-size: 36px;
                letter-spacing: 6px;
            }

            .action-button {
                width: 100%;
                padding: 16px 24px;
            }

            .contact-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .footer-links {
                gap: 16px;
            }

            .tips-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .code {
                font-size: 32px;
                letter-spacing: 4px;
            }

            .footer-links {
                flex-direction: column;
                gap: 12px;
            }

            .step {
                padding-left: 24px;
            }
        }

        /* Print Styles */
        @media print {
            .action-button {
                display: none;
            }
            
            .code-container::before {
                display: none;
            }
            
            .timer {
                background: none;
                border: 1px solid #ccc;
                color: #333;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="header-icon">🔐</div>
            <div class="logo">{{ $appName }}</div>
            <div class="subtitle">Password Reset Request</div>
        </div>

        <!-- Main Content -->
        <div class="content">
            <h1 class="greeting">
                Hi @if($name)<span class="user-name">{{ $name }}</span>@else there @endif! 👋
            </h1>

            <p class="message">
                We received a request to reset your password. Use the verification code below
                to securely reset your account password:
            </p>

            <!-- Reset Code -->
            <div class="code-container">
                <div class="code-label">Reset Code</div>
                <div class="code">{{ $code }}</div>
                <div class="timer">
                    ⏰ Expires in {{ $expiresIn }} minutes
                </div>
            </div>

            <!-- Action Button -->
            <div class="action-container">
                <a href="{{ $resetPasswordUrl }}?code={{ $code }}&email={{ urlencode(request()->email ?? '') }}" 
                   class="action-button">
                    Reset Password Now
                </a>
            </div>

            <!-- Instructions -->
            <div class="instructions">
                <div class="instructions-title">
                    📝 How to reset your password:
                </div>
                <ul class="steps">
                    <li class="step">Enter the reset code above</li>
                    <li class="step">Create a new strong password</li>
                    <li class="step">Confirm your new password</li>
                    <li class="step">Log in with your new password</li>
                </ul>
            </div>

            <!-- Password Tips -->
            <div class="tips-box">
                <div class="tips-title">
                    🔑 Password Tips
                </div>
                <div class="tips-grid">
                    <div class="tip-item">✓ Minimum 8 characters</div>
                    <div class="tip-item">✓ Mix letters & numbers</div>
                    <div class="tip-item">✓ Use special characters</div>
                    <div class="tip-item">✓ Avoid common passwords</div>
                </div>
            </div>

            <!-- Warning -->
            <div class="warning-box">
                <div class="warning-title">
                    ⚠️ Security Alert
                </div>
                <p style="color: var(--text-secondary); font-size: 14px; margin: 0;">
                    If you didn't request this password reset, please ignore this email or
                    <a href="{{ $securityCenterUrl }}" style="color: var(--warning-color); font-weight: 600; text-decoration: none;">
                        secure your account
                    </a>
                    immediately.
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p class="footer-text">
                For your security, this code will expire in <strong>{{ $expiresIn }} minutes</strong>.
                Never share this code with anyone.
            </p>

            <div class="contact-grid">
                <div class="contact-item">
                    <div class="contact-icon">📧</div>
                    <div class="contact-label">Email Support</div>
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
                    <div class="contact-value">24/7 Available</div>
                </div>
            </div>

            <div class="footer-links">
                <a href="{{ $securityCenterUrl }}" class="footer-link">Security Center</a>
                <a href="{{ $helpCenterUrl }}" class="footer-link">Help Center</a>
                <a href="mailto:support@wonderwaypictures.com" class="footer-link">Contact Support</a>
            </div>

            <div class="copyright">
                © {{ $currentYear }} {{ $appName }}. All rights reserved.<br>
                <span style="opacity: 0.6;">Your security is our priority</span>
            </div>
        </div>
    </div>
</body>
</html>