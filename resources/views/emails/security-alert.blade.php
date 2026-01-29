<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Security Alert</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f5f5f5; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background-color: white; border-radius: 8px; padding: 30px;">
        <h1 style="color: #dc3545; text-align: center; margin-bottom: 30px;">🚨 Security Alert</h1>
        
        <p>Hello {{ $user->name }},</p>
        
        <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; padding: 15px; margin: 20px 0;">
            <h3 style="margin: 0 0 10px 0; color: #856404;">{{ $alertData['message'] }}</h3>
            <p style="margin: 0; color: #856404;">Time: {{ $alertData['timestamp'] }}</p>
        </div>
        
        @if(isset($alertData['data']['ip']))
        <div style="background-color: #f8f9fa; border-radius: 6px; padding: 15px; margin: 20px 0;">
            <h3 style="margin: 0 0 10px 0; color: #333;">Event Details:</h3>
            <p style="margin: 5px 0;"><strong>IP Address:</strong> {{ $alertData['data']['ip'] ?? 'Unknown' }}</p>
            @if(isset($alertData['data']['user_agent']))
            <p style="margin: 5px 0;"><strong>Device:</strong> {{ $alertData['data']['user_agent'] }}</p>
            @endif
        </div>
        @endif
        
        <div style="background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 6px; padding: 15px; margin: 20px 0;">
            <p style="margin: 0; color: #721c24;">
                <strong>⚠️ If this wasn't you:</strong><br>
                • Change your password immediately<br>
                • Enable two-factor authentication<br>
                • Review your account activity<br>
                • Contact support if needed
            </p>
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ config('app.url') }}/settings" 
               style="background-color: #dc3545; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;">
                Secure My Account
            </a>
        </div>
        
        <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
        
        <p style="color: #999; font-size: 12px; text-align: center;">
            © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.<br>
            This is an automated security alert, please do not reply.
        </p>
    </div>
</body>
</html>