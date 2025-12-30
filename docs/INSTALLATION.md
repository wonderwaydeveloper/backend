# Installation Guide

## Prerequisites

### System Requirements
- **PHP 8.2+** with extensions: `pdo_mysql`, `mbstring`, `exif`, `pcntl`, `bcmath`, `gd`, `zip`, `opcache`, `redis`, `sockets`
- **Composer 2.0+**
- **Node.js 18+** and **npm**
- **MySQL 8.0+** or **MariaDB 10.6+**
- **Redis 7.0+**

### Development Tools
- **Git**
- **Docker & Docker Compose** (optional)
- **FFmpeg** (for video processing)

## Installation Methods

### Method 1: Local Installation

#### 1. Clone Repository
```bash
git clone https://github.com/your-username/wonderway-backend.git
cd wonderway-backend
```

#### 2. Install Dependencies
```bash
# PHP dependencies
composer install

# Node.js dependencies
npm install
```

#### 3. Environment Setup
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

#### 4. Database Configuration
```bash
# Edit .env file
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wonderway
DB_USERNAME=<your-username>
DB_PASSWORD=<your-password>

# Run migrations
php artisan migrate

# Seed database
php artisan db:seed
```

#### 5. Redis Configuration
```bash
# In .env file
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

#### 6. Start Services
```bash
# Laravel server
php artisan serve

# Queue worker
php artisan queue:work

# WebSocket server
php artisan reverb:start
```

### Method 2: Docker Installation (Recommended)

#### 1. Clone and Setup
```bash
git clone https://github.com/your-username/wonderway-backend.git
cd wonderway-backend
cp .env.example .env
```

#### 2. Build and Run
```bash
# Build and start containers
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate

# Seed database
docker-compose exec app php artisan db:seed
```

## Admin Panel Setup

```bash
# Create admin user
php artisan db:seed --class=AdminSeeder

# Access admin panel
# URL: http://localhost:8000/admin
# Email: <admin-email>
# Password: <admin-password>
```

## Environment Configuration

### Required Variables
```env
APP_NAME=WonderWay
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:3000

JWT_SECRET=<your-jwt-secret>

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wonderway
DB_USERNAME=<your-username>
DB_PASSWORD=<your-password>

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Email
MAIL_MAILER=smtp
MAIL_HOST=<your-smtp-host>
MAIL_PORT=587
MAIL_USERNAME=<your-email>
MAIL_PASSWORD=<your-password>
```

## Verification

### Test Installation
```bash
# Run tests
php artisan test

# Check application status
php artisan about
```

### Access Points
- **Application**: http://localhost:8000
- **Admin Panel**: http://localhost:8000/admin
- **API Documentation**: http://localhost:8000/api/documentation

## Next Steps

1. Read [API Documentation](API.md)
2. Explore [Admin Panel Guide](ADMIN.md)
3. Check [Troubleshooting](TROUBLESHOOTING.md) if needed