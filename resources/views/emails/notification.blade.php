<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background-color: white; padding: 20px; border-radius: 8px; }
        .header { text-align: center; border-bottom: 2px solid #1DA1F2; padding-bottom: 20px; }
        .header h1 { color: #1DA1F2; margin: 0; }
        .content { padding: 20px 0; }
        .button { display: inline-block; background-color: #1DA1F2; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
        .footer { text-align: center; color: #666; font-size: 12px; border-top: 1px solid #eee; padding-top: 20px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>New Notification</h1>
        </div>
        <div class="content">
            <p>Hello {{ $user->name }},</p>
            <p>{{ $notification->message }}</p>
            <p>
                <a href="{{ config('app.url') }}" class="button">View Details</a>
            </p>
        </div>
        <div class="footer">
            <p>© {{ date('Y') }} WonderWay. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
