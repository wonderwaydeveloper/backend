# Media System Deployment Guide

## Prerequisites

### 1. Install FFMpeg
```bash
# Ubuntu/Debian
sudo apt update
sudo apt install ffmpeg

# Verify
ffmpeg -version
```

### 2. Install PHP Extensions
```bash
sudo apt install php-gd php-imagick
```

### 3. Install Composer Dependencies
```bash
composer require php-ffmpeg/php-ffmpeg
composer require intervention/image
```

## Configuration

### 1. Environment Variables
Add to `.env`:
```env
FFMPEG_BINARIES=/usr/bin/ffmpeg
FFPROBE_BINARIES=/usr/bin/ffprobe
MEDIA_STORAGE_DISK=public
QUEUE_CONNECTION=redis
```

### 2. Storage Permissions
```bash
chmod -R 775 storage/app/public
chown -R www-data:www-data storage/app/public
php artisan storage:link
```

### 3. Queue Worker Setup

#### Using Supervisor (Recommended)
```bash
sudo cp supervisor-queue-worker.conf /etc/supervisor/conf.d/clevlance-worker.conf
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start clevlance-worker:*
```

#### Manual Start (Development)
```bash
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

## Testing

### 1. Test Image Upload
```bash
curl -X POST http://localhost/api/media/upload/image \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "image=@test.jpg"
```

### 2. Test Video Upload
```bash
curl -X POST http://localhost/api/media/upload/video \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "video=@test.mp4"
```

### 3. Check Queue Status
```bash
php artisan queue:work --once
php artisan queue:failed
```

### 4. Monitor Logs
```bash
tail -f storage/logs/laravel.log
tail -f storage/logs/worker.log
```

## Troubleshooting

### Queue Not Processing
```bash
# Check queue worker
sudo supervisorctl status clevlance-worker:*

# Restart queue worker
sudo supervisorctl restart clevlance-worker:*

# Check failed jobs
php artisan queue:failed
php artisan queue:retry all
```

### FFMpeg Not Found
```bash
# Find FFMpeg path
which ffmpeg
which ffprobe

# Update .env
FFMPEG_BINARIES=/path/to/ffmpeg
FFPROBE_BINARIES=/path/to/ffprobe
```

### Storage Permission Issues
```bash
sudo chown -R www-data:www-data storage
sudo chmod -R 775 storage
```

### Memory Issues
Edit `php.ini`:
```ini
memory_limit = 512M
upload_max_filesize = 2048M
post_max_size = 2048M
max_execution_time = 3600
```

## Monitoring

### Check Media Processing Status
```sql
SELECT encoding_status, COUNT(*) 
FROM media 
WHERE type = 'video' 
GROUP BY encoding_status;
```

### Check Failed Jobs
```bash
php artisan queue:failed
```

### Clear Failed Jobs
```bash
php artisan queue:flush
```

## Performance Optimization

### 1. Use Multiple Queue Workers
Edit `supervisor-queue-worker.conf`:
```ini
numprocs=8
```

### 2. Separate Video Queue
```bash
php artisan queue:work --queue=videos,default
```

### 3. Use Redis for Queue
```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

## Security Checklist

- [ ] FFMpeg installed and configured
- [ ] Queue worker running
- [ ] Storage permissions correct
- [ ] File validation enabled
- [ ] Rate limiting configured
- [ ] Logs monitored
- [ ] Failed jobs handled
- [ ] Backup strategy in place

## Production Checklist

- [ ] `.env` configured
- [ ] FFMpeg installed
- [ ] Queue worker running (supervisor)
- [ ] Storage linked
- [ ] Permissions set
- [ ] Logs rotating
- [ ] Monitoring enabled
- [ ] Backup configured
- [ ] CDN configured (optional)
- [ ] Load testing completed
