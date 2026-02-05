# Email Verification Template و Security System

## نمای کلی
سیستم جامع email templates با قابلیتهای امنیتی پیشرفته، rate limiting، و محافظت در برابر تهدیدات.

## فایلهای ایجاد شده/بهبود یافته

### 1. Email Security Configuration
**مسیر**: `config/email_security.php`

**قابلیتها**:
- Rate limiting برای انواع مختلف email
- Validation settings برای کدهای تایید
- Anti-spam و domain blacklist
- Template customization
- Security headers configuration

### 2. Enhanced EmailService
**مسیر**: `app/Services/EmailService.php`

**بهبودهای امنیتی**:
- ✅ Rate limiting برای جلوگیری از spam
- ✅ Email domain validation و blacklist
- ✅ Email masking در logs
- ✅ IP masking برای privacy
- ✅ Audit logging integration
- ✅ Security event tracking

### 3. Secure Email Templates
**مسیرها**: 
- `resources/views/emails/verification.blade.php`
- `resources/views/emails/password-reset.blade.php`

**ویژگیهای امنیتی**:
- ✅ Content Security Policy headers
- ✅ XSS protection با HTML escaping
- ✅ No-referrer policy
- ✅ Security notices و warnings
- ✅ Configurable branding
- ✅ Support contact information

### 4. SecureEmail Validation Rule
**مسیر**: `app/Rules/SecureEmail.php`

**بررسیهای امنیتی**:
- ✅ Basic email format validation
- ✅ Domain blacklist checking
- ✅ Suspicious pattern detection
- ✅ Disposable email detection
- ✅ XSS/injection prevention

### 5. Email Testing Command
**مسیر**: `app/Console/Commands/TestEmailTemplatesCommand.php`

**استفاده**:
```bash
php artisan email:test verification user@example.com --code=123456
php artisan email:test password-reset user@example.com --code=654321
php artisan email:test device-verification user@example.com --code=789012
```

## تنظیمات امنیتی

### Rate Limiting
```php
'rate_limiting' => [
    'enabled' => true,
    'verification' => [
        'max_attempts' => 3,
        'window_minutes' => 60,
    ],
    'password_reset' => [
        'max_attempts' => 2,
        'window_minutes' => 60,
    ],
    'device_verification' => [
        'max_attempts' => 3,
        'window_minutes' => 15,
    ],
],
```

### Anti-Spam Protection
```php
'anti_spam' => [
    'honeypot_enabled' => true,
    'captcha_threshold' => 3,
    'blacklist_domains' => [
        '10minutemail.com',
        'tempmail.org',
        'guerrillamail.com',
    ],
],
```

### Security Headers در Templates
```html
<meta http-equiv="Content-Security-Policy" content="default-src 'self'; style-src 'unsafe-inline'; img-src 'self' data:;">
<meta name="referrer" content="no-referrer">
```

## ویژگیهای امنیتی

### 1. Rate Limiting
- محدودیت تعداد email در بازه زمانی مشخص
- جلوگیری از spam و abuse
- Cache-based implementation

### 2. Email Validation
- بررسی فرمت email
- Domain blacklist checking
- Disposable email detection
- Suspicious pattern detection

### 3. Content Security
- XSS protection با HTML escaping
- Content Security Policy headers
- No external resource loading
- Safe inline styles only

### 4. Privacy Protection
- Email masking در logs
- IP address masking
- No tracking pixels
- No click tracking

### 5. User Education
- Security notices در emails
- Clear instructions
- Warning messages
- Support contact information

## Email Templates

### Verification Email
- **Subject**: Email Verification - App Name
- **Features**: Security notice, configurable branding, clear CTA
- **Security**: XSS protection, CSP headers

### Password Reset Email
- **Subject**: Password Reset - App Name
- **Features**: Security warnings, detailed instructions
- **Security**: Enhanced warnings, suspicious activity guidance

### Device Verification Email
- **Subject**: Device Verification - App Name
- **Features**: Device info display, security alerts
- **Security**: IP masking, location info

## API Integration

### EmailService Methods
```php
// با rate limiting و security checks
$emailService->sendVerificationEmail($user, $code);
$emailService->sendPasswordResetEmail($user, $code);
$emailService->sendDeviceVerificationEmail($user, $code, $deviceInfo);
```

### Validation در Controllers
```php
$request->validate([
    'email' => ['required', new SecureEmail()],
]);
```

## Monitoring و Logging

### Security Events
```php
// خودکار در EmailService
$this->auditService->logSecurityEvent('email_sent', [
    'type' => 'verification',
    'recipient' => $this->maskEmail($user->email)
]);
```

### Rate Limit Monitoring
```php
// Cache-based tracking
Cache::increment("email_rate_limit:verification:{$email}");
```

## Environment Variables

```env
# Email Security
EMAIL_RATE_LIMIT_ENABLED=true
EMAIL_BRAND_COLOR=#1DA1F2
EMAIL_LOGO_URL=https://example.com/logo.png
EMAIL_SUPPORT=support@example.com

# Mail Security
MAIL_DKIM_ENABLED=true
MAIL_SPF_ENABLED=true
```

## Testing

### Manual Testing
```bash
# Test verification email
php artisan email:test verification user@example.com

# Test password reset
php artisan email:test password-reset user@example.com

# Test device verification
php artisan email:test device-verification user@example.com
```

### Security Testing
- Rate limiting effectiveness
- Domain blacklist functionality
- XSS protection in templates
- Email masking in logs

## Best Practices

### 1. Security
- همیشه از HTML escaping استفاده کنید
- Rate limiting را فعال نگه دارید
- Domain blacklist را بهروز نگه دارید
- Security headers را در templates اضافه کنید

### 2. Privacy
- Email addresses را در logs mask کنید
- IP addresses را mask کنید
- Tracking را غیرفعال نگه دارید

### 3. User Experience
- Clear و actionable messages
- Security education
- Support contact information
- Mobile-friendly templates

## مزایا

✅ **امنیت**: Rate limiting، domain validation، XSS protection
✅ **Privacy**: Email/IP masking، no tracking
✅ **User Experience**: Clear templates، security education
✅ **Monitoring**: Comprehensive logging، audit trail
✅ **Customization**: Configurable branding، templates
✅ **Testing**: Built-in testing commands
✅ **Performance**: Cache-based rate limiting
✅ **Compliance**: Security headers، best practices

## نتیجه

سیستم Email Templates و Security به طور کامل پیادهسازی شد و شامل:
- Templates امن با security headers
- Rate limiting و anti-spam protection
- Email validation و domain blacklist
- Privacy protection و data masking
- Comprehensive monitoring و audit logging
- Testing tools و documentation