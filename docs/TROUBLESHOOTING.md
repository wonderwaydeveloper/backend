# Troubleshooting Guide

## Installation Issues

### Composer Errors

#### Memory Limit Error
```bash
# Solution: Increase memory limit
php -d memory_limit=-1 /usr/local/bin/composer install
```

#### Package Conflicts
```bash
# Clear composer cache
composer clear-cache

# Update dependencies
composer update --with-dependencies
```

### Database Issues

#### Connection Failed
```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();
```

**Solutions:**
- Verify database credentials in `.env`
- Ensure MySQL/MariaDB is running
- Check firewall settings
- Verify database exists

#### Migration Errors
```bash
# Reset migrations (WARNING: Data loss)
php artisan migrate:fresh

# Rollback specific migration
php artisan migrate:rollback --step=1
```

### Redis Issues

#### Connection Refused
```bash
# Check Redis status
redis-cli ping
# Should return: PONG
```

**Solutions:**
- Start Redis service: `redis-server`
- Check Redis configuration
- Verify port 6379 is available
- Update Redis credentials in `.env`

## Performance Issues

### Slow Timeline Loading

#### Clear Application Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

#### Optimize Application
```bash
php artisan optimize
php artisan config:cache
php artisan route:cache
```

### Queue Problems

#### Queue Not Processing
```bash
# Restart queue workers
php artisan queue:restart

# Start queue worker with retry
php artisan queue:work --tries=3 --timeout=60
```

#### Failed Jobs
```bash
# View failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

## Security Issues

### CORS Errors

#### Frontend Can't Access API
**Check CORS configuration in `config/cors.php`:**
```php
'allowed_origins' => [
    env('FRONTEND_URL', 'http://localhost:3000'),
],
```

**Verify environment variables:**
```env
FRONTEND_URL=http://localhost:3000
```

### JWT Token Issues

#### Invalid Token Errors
```bash
# Generate new JWT secret
php artisan jwt:secret

# Clear auth cache
php artisan auth:clear-resets
```

#### Token Expired
- Check JWT TTL configuration
- Implement token refresh logic
- Verify system time synchronization

## Admin Panel Issues

### Can't Access Admin Panel

#### Permission Denied
```bash
# Check user roles
php artisan tinker
>>> User::find(1)->roles;
```

#### Create Admin User
```bash
php artisan db:seed --class=AdminSeeder
```

### Filament Errors

#### Resource Not Found
- Verify resource registration in `AdminPanelProvider`
- Check namespace imports
- Clear application cache

## API Issues

### 500 Internal Server Error

#### Check Application Logs
```bash
# View latest logs
tail -f storage/logs/laravel.log

# Check specific error
php artisan log:show
```

#### Debug Mode
```env
# Enable debug mode (development only)
APP_DEBUG=true
```

### Rate Limiting

#### Too Many Requests (429)
- Check rate limit configuration
- Implement exponential backoff
- Use authentication to increase limits

## WebSocket Issues

### Real-time Features Not Working

#### Reverb Server Issues
```bash
# Start WebSocket server
php artisan reverb:start

# Check server status
php artisan reverb:status
```

#### Client Connection Problems
- Verify WebSocket URL configuration
- Check firewall settings for WebSocket ports
- Test with WebSocket client tools

## File Upload Issues

### Upload Fails

#### File Size Limits
```php
// Check PHP configuration
ini_get('upload_max_filesize');
ini_get('post_max_size');
```

#### Storage Permissions
```bash
# Fix storage permissions
chmod -R 775 storage/
chown -R www-data:www-data storage/
```

## Testing Issues

### Tests Failing

#### Database Issues
```bash
# Use separate test database
DB_DATABASE=wonderway_test

# Refresh test database
php artisan migrate:fresh --env=testing
```

#### Environment Issues
```bash
# Copy test environment
cp .env.testing.example .env.testing

# Run specific test
php artisan test --filter=TimelineTest
```

## Common Error Messages

### "Class not found"
- Run `composer dump-autoload`
- Check namespace declarations
- Verify file locations

### "Route not defined"
- Clear route cache: `php artisan route:clear`
- Check route registration
- Verify middleware groups

### "View not found"
- Clear view cache: `php artisan view:clear`
- Check view file paths
- Verify view namespace

## Performance Monitoring

### Slow Queries
```bash
# Enable query logging
DB_LOG_QUERIES=true

# Monitor slow queries
tail -f storage/logs/queries.log
```

### Memory Usage
```bash
# Check memory usage
php artisan about

# Monitor with htop
htop
```

## Getting Help

### Debug Information
When reporting issues, include:
- Laravel version: `php artisan --version`
- PHP version: `php --version`
- Error messages from logs
- Steps to reproduce
- Environment details

### Log Files
- **Application**: `storage/logs/laravel.log`
- **Web Server**: Check Apache/Nginx logs
- **Database**: Check MySQL/MariaDB logs
- **Queue**: `storage/logs/worker.log`

### Support Channels
- GitHub Issues for bug reports
- Documentation for common solutions
- Community forums for discussions