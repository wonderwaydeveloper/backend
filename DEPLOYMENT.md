# Clevlance - Deployment Guide

## 🚀 Quick Start

### Prerequisites
- Docker & Docker Compose
- Git
- Domain with SSL certificate

### Environment Setup

1. Clone repository:
```bash
git clone https://github.com/your-org/clevlance.git
cd clevlance/backend
```

2. Copy environment file:
```bash
cp .env.example .env
```

3. Configure `.env`:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.clevlance.com

DB_PASSWORD=your_secure_password
DB_ROOT_PASSWORD=your_root_password

REDIS_PASSWORD=your_redis_password
```

4. Generate application key:
```bash
docker-compose run --rm app php artisan key:generate
```

### Docker Deployment

1. Build and start containers:
```bash
docker-compose up -d --build
```

2. Run migrations:
```bash
docker-compose exec app php artisan migrate --force
```

3. Seed admin user:
```bash
docker-compose exec app php artisan db:seed --class=AdminSeeder
```

4. Optimize:
```bash
docker-compose exec app php artisan optimize
```

### SSL Configuration

Place SSL certificates in `docker/ssl/`:
```
docker/ssl/
├── cert.pem
└── key.pem
```

Update `docker/nginx.conf` for HTTPS.

### Health Check

```bash
curl http://localhost/api/health
```

Expected response:
```json
{
  "status": "ok",
  "database": "ok",
  "cache": "ok",
  "timestamp": "2024-01-01T00:00:00Z"
}
```

## 📊 Monitoring

- **Prometheus**: http://localhost:9090
- **Grafana**: http://localhost:3000 (admin/admin123)

## 🔒 Security Checklist

- [ ] Change all default passwords
- [ ] Configure CORS origins
- [ ] Enable SSL/TLS
- [ ] Set up firewall rules
- [ ] Configure rate limiting
- [ ] Enable 2FA for admin
- [ ] Set up backup strategy
- [ ] Configure monitoring alerts

## 🔄 Updates

```bash
cd /var/www/clevlance
bash deploy.sh
```

## 📝 Logs

```bash
# Application logs
docker-compose logs -f app

# Nginx logs
docker-compose logs -f nginx

# Queue logs
docker-compose logs -f queue
```

## 🆘 Troubleshooting

### Database connection failed
```bash
docker-compose restart mysql
docker-compose exec app php artisan migrate:status
```

### Cache issues
```bash
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
```

### Permission errors
```bash
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec app chmod -R 755 storage bootstrap/cache
```

## 📞 Support

- Documentation: https://docs.clevlance.com
- Issues: https://github.com/your-org/clevlance/issues
- Email: support@clevlance.com
