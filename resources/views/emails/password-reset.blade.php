<!DOCTYPE html>
<html dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>بازیابی رمز عبور</title>
    <style>
        body { font-family: 'Tahoma', 'Arial', sans-serif; direction: rtl; text-align: right; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #dc2626; color: white; padding: 20px; text-align: center; }
        .content { background: #f9fafb; padding: 30px; }
        .code { font-size: 32px; font-weight: bold; text-align: center; letter-spacing: 8px; color: #dc2626; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>بازیابی رمز عبور</h1>
        </div>
        
        <div class="content">
            <p>سلام {{ $name }}،</p>
            <p>برای بازیابی رمز عبور حساب کاربری خود، لطفاً از کد زیر استفاده کنید:</p>
            
            <div class="code">{{ $code }}</div>
            
            <p>این کد تا <strong>{{ $expiresIn }} دقیقه</strong> دیگر معتبر است.</p>
            
            <p>اگر شما این درخواست را نکرده‌اید، لطفاً این ایمیل را نادیده بگیرید.</p>
        </div>
        
        <div class="footer">
            <p>با تشکر از شما،<br>{{ config('app.name') }}</p>
        </div>
    </div>
</body>
</html>