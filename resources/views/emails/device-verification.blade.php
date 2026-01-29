<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>New Device Login</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f5f5f5; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background-color: white; border-radius: 8px; padding: 30px;">
        <h1 style="color: #dc3545; text-align: center; margin-bottom: 30px;">🔐 New Device Login</h1>
        
        <p>Hello {{ $user->name }},</p>
        
        <p>We detected a login from a new device. If this was you, please verify this device using the code below:</p>
        
        <div style="background-color: #fff5f5; border: 2px dashed #dc3545; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0;">
            <span style="font-size: 32px; font-weight: bold; color: #dc3545; letter-spacing: 4px; font-family: monospace;">{{ $code }}</span>
        </div>
        
        <div style="background-color: #f8f9fa; border-radius: 6px; padding: 15px; margin: 20px 0;">
            <h3 style="margin: 0 0 10px 0; color: #333;">Device Information:</h3>
            <p style="margin: 5px 0;"><strong>Device:</strong> {{ $deviceInfo['device_info'] ?? 'Unknown' }}</p>
            <p style="margin: 5px 0;"><strong>Location:</strong> {{ $deviceInfo['location'] ?? 'Unknown' }}</p>
            <p style="margin: 5px 0;"><strong>IP Address:</strong> {{ $deviceInfo['ip'] ?? 'Unknown' }}</p>
        </div>
        
        <p style="color: #dc3545; font-weight: bold;">⚠️ If this wasn't you, please secure your account immediately by changing your password.</p>
        
        <p style="color: #666; font-size: 14px;">This code is valid for 15 minutes.</p>
        
        <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
        
        <p style="color: #999; font-size: 12px; text-align: center;">
            © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.<br>
            This is an automated message, please do not reply.
        </p>
    </div>
</body>
</html>