<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .content {
            padding: 30px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #666;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ config('app.name') }}</h1>
            <h2>{{ $subject }}</h2>
        </div>
        
        <div class="content">
            <p>Hello {{ $user->name }},</p>
            
            @if(isset($content))
                {!! $content !!}
            @else
                <p>This is an informational email from {{ config('app.name') }}.</p>
            @endif
            
            @if(isset($action_url) && isset($action_text))
                <p style="text-align: center;">
                    <a href="{{ $action_url }}" class="btn">{{ $action_text }}</a>
                </p>
            @endif
        </div>
        
        <div class="footer">
            <p>Best regards,<br>{{ config('app.name') }} Team</p>
            <p><small>If you received this email by mistake, please ignore it.</small></p>
        </div>
    </div>
</body>
</html>