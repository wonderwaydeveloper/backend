<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; style-src 'unsafe-inline'; img-src 'self' data:;">
    <meta name="referrer" content="no-referrer">
    <title>Email Verification - {{ config('app.name') }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
</head>
<body style="margin: 0; padding: 0; background-color: #f5f5f5; font-family: Arial, sans-serif; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f5f5f5;">
        <tr>
            <td align="center" style="padding: 20px 0;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="max-width: 600px; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td align="center" style="padding: 30px 20px 20px; border-bottom: 2px solid {{ config('authentication.email.templates.brand_color', '#1DA1F2') }};">
                            @if(config('authentication.email.templates.logo_url'))
                                <img src="{{ config('authentication.email.templates.logo_url') }}" alt="{{ config('app.name') }}" style="max-height: 50px; margin-bottom: 15px;">
                            @endif
                            <h1 style="margin: 0; color: {{ config('authentication.email.templates.brand_color', '#1DA1F2') }}; font-size: 28px; font-weight: bold;">Email Verification</h1>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding: 30px 20px;">
                            <p style="margin: 0 0 20px; color: #333333; font-size: 16px; line-height: 1.5;">Hello {{ e($user->name ?? 'User') }},</p>
                            <p style="margin: 0 0 25px; color: #333333; font-size: 16px; line-height: 1.5;">To verify your email address, please use the verification code below:</p>
                            
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td align="center" style="padding: 20px 0;">
                                        <div style="background-color: #f8f9fa; border: 2px dashed {{ config('authentication.email.templates.brand_color', '#1DA1F2') }}; border-radius: 8px; padding: 20px; display: inline-block;">
                                            <span style="font-size: 32px; font-weight: bold; color: {{ config('authentication.email.templates.brand_color', '#1DA1F2') }}; letter-spacing: 4px; font-family: 'Courier New', monospace;">{{ $code }}</span>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="margin: 25px 0 0; color: #666666; font-size: 14px; line-height: 1.5;">This code is valid for {{ config('authentication.email.verification_expire_minutes', 15) }} minutes.</p>
                            <p style="margin: 15px 0 0; color: #666666; font-size: 14px; line-height: 1.5;">If you didn't request this verification, please ignore this email.</p>
                            
                            <!-- Security Notice -->
                            <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; padding: 15px; margin: 20px 0;">
                                <p style="margin: 0; color: #856404; font-size: 13px; line-height: 1.4;">
                                    <strong>🔒 Security Notice:</strong> Never share this code with anyone. {{ config('app.name') }} will never ask for your verification code.
                                </p>
                            </div>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding: 20px; border-top: 1px solid #eeeeee; background-color: #f8f9fa;">
                            <p style="margin: 0; color: #999999; font-size: 12px;">© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                            <p style="margin: 5px 0 0; color: #999999; font-size: 12px;">This is an automated message, please do not reply.</p>
                            @if(config('authentication.email.templates.support_email'))
                                <p style="margin: 5px 0 0; color: #999999; font-size: 12px;">Need help? Contact us at {{ config('authentication.email.templates.support_email') }}</p>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
