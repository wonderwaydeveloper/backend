# Deployment Guide - Authentication System

## Pre-deployment Checklist

### 1. Environment Configuration
```bash
# Copy and configure environment
cp .env.example .env

# Generate application key
php artisan key:generate

# Set production values
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Set strong JWT secret (256-bit)
JWT_SECRET=your-strong-256-bit-secret-key-here
JWT_ACCESS_TTL=900
JWT_REFRESH_TTL=86400

# Database configuration
DB_PASSWORD=strong-database-password

# Security settings
SECURITY_WAF_ENABLED=true
SECURITY_RATE_LIMIT_ENABLED=true
SESSION_SECURE_COOKIE=true
```

### 2. Dependencies Installation
```bash
composer install --no-dev --optimize-autoloader
```

### 3. Database Setup
```bash
php artisan migrate --force
php artisan db:seed --class=RoleSeeder
```

### 4. Cache Optimization
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 5. Security Audit
```bash
php artisan security:audit
```

### 6. File Permissions
```bash
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

## Post-deployment Verification

1. Test authentication endpoints
2. Verify middleware functionality
3. Check security headers
4. Test rate limiting
5. Verify JWT token generation

## Monitoring

- Monitor security logs: `storage/logs/security.log`
- Check performance logs: `storage/logs/performance.log`
- Run periodic security audits

## Security Score Target

- Minimum acceptable score: 80/100
- Production ready score: 90/100