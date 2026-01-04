<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Welcome to {{ config('app.name') }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f5f5f5; font-family: Arial, sans-serif; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f5f5f5;">
        <tr>
            <td align="center" style="padding: 20px 0;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="max-width: 600px; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td align="center" style="padding: 30px 20px 20px; border-bottom: 2px solid #1DA1F2;">
                            <h1 style="margin: 0; color: #1DA1F2; font-size: 28px; font-weight: bold;">Welcome to {{ config('app.name') }}</h1>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding: 30px 20px;">
                            <p style="margin: 0 0 20px; color: #333333; font-size: 16px; line-height: 1.5;">Hello {{ $user->name ?? 'User' }},</p>
                            <p style="margin: 0 0 25px; color: #333333; font-size: 16px; line-height: 1.5;">Thank you for joining {{ config('app.name') }}! We're excited to have you as part of our community.</p>
                            
                            <p style="margin: 0 0 15px; color: #333333; font-size: 16px; line-height: 1.5;">Now you can start to:</p>
                            <ul style="margin: 0 0 25px; padding-left: 20px; color: #333333; font-size: 16px; line-height: 1.8;">
                                <li>Create and share your posts</li>
                                <li>Follow other users and discover content</li>
                                <li>Connect with friends and build your network</li>
                                <li>Explore trending topics and discussions</li>
                            </ul>
                            
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td align="center" style="padding: 20px 0;">
                                        <a href="{{ config('app.frontend_url', config('app.url')) }}" style="display: inline-block; background-color: #1DA1F2; color: #ffffff; padding: 15px 30px; text-decoration: none; border-radius: 6px; font-size: 16px; font-weight: bold;">Get Started</a>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="margin: 25px 0 0; color: #666666; font-size: 14px; line-height: 1.5;">If you have any questions, feel free to contact our support team.</p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding: 20px; border-top: 1px solid #eeeeee; background-color: #f8f9fa;">
                            <p style="margin: 0; color: #999999; font-size: 12px;">© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                            <p style="margin: 5px 0 0; color: #999999; font-size: 12px;">This is an automated message, please do not reply.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
