# راهنمای دیپلویمنت WonderWay

## مقدمه

این راهنما شامل تمام مراحل و بهترین شیوههای دیپلویمنت پلتفرم WonderWay در محیطهای مختلف است.

## فهرست مطالب

- [پیشنیازهای دیپلویمنت](#پیشنیازهای-دیپلویمنت)
- [دیپلویمنت با Docker](#دیپلویمنت-با-docker)
- [دیپلویمنت Manual](#دیپلویمنت-manual)
- [Cloud Deployment](#cloud-deployment)
- [CI/CD Pipeline](#cicd-pipeline)
- [مانیتورینگ و لاگگیری](#مانیتورینگ-و-لاگگیری)
- [بکاپ و بازیابی](#بکاپ-و-بازیابی)
- [عیبیابی](#عیبیابی)

---

## پیشنیازهای دیپلویمنت

### سیستم عامل
- **Ubuntu 20.04 LTS** یا **22.04 LTS** (توصیه شده)
- **CentOS 8** یا **Rocky Linux 8**
- **Amazon Linux 2**

### منابع سیستم

#### حداقل (برای تست)
- **CPU**: 2 cores
- **RAM**: 4GB
- **Storage**: 50GB SSD
- **Network**: 100 Mbps

#### توصیه شده (برای production)
- **CPU**: 8+ cores
- **RAM**: 16GB+
- **Storage**: 200GB+ NVMe SSD
- **Network**: 1 Gbps+

#### مقیاس بالا (برای میلیونها کاربر)
- **CPU**: 32+ cores
- **RAM**: 64GB+
- **Storage**: 1TB+ NVMe SSD
- **Network**: 10 Gbps+

### نرمافزارهای مورد نیاز

```bash
# بروزرسانی سیستم
sudo apt update && sudo apt upgrade -y

# نصب dependencies اصلی
sudo apt install -y \
    curl \
    wget \
    git \
    unzip \
    software-properties-common \
    apt-transport-https \
    ca-certificates \
    gnupg \
    lsb-release
```

---

## دیپلویمنت با Docker

### 1. نصب Docker و Docker Compose

```bash
# نصب Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# اضافه کردن کاربر به گروه docker
sudo usermod -aG docker $USER

# نصب Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# تأیید نصب
docker --version
docker-compose --version
```

### 2. آمادهسازی پروژه

```bash
# کلون پروژه
git clone https://github.com/your-org/wonderway-backend.git
cd wonderway-backend

# کپی فایل محیط
cp .env.production .env

# ویرایش متغیرهای محیط
nano .env
```

### 3. پیکربندی Production

#### docker-compose.production.yml

```yaml
version: '3.8'

services:
  # Application
  app:
    build:
      context: .
      dockerfile: Dockerfile.production
    container_name: wonderway-app
    restart: unless-stopped
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
    volumes:
      - ./storage:/var/www/html/storage
      - ./bootstrap/cache:/var/www/html/bootstrap/cache
    networks:
      - wonderway-network
    depends_on:
      - mysql
      - redis
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost/health"]
      interval: 30s
      timeout: 10s
      retries: 3

  # Load Balancer
  nginx:
    image: nginx:alpine
    container_name: wonderway-nginx
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./docker/nginx/production.conf:/etc/nginx/nginx.conf
      - ./docker/ssl:/etc/nginx/ssl
      - ./storage/app/public:/var/www/html/storage/app/public
    networks:
      - wonderway-network
    depends_on:
      - app

  # Database
  mysql:
    image: mysql:8.0
    container_name: wonderway-mysql
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MYSQL_DATABASE: ${DB_DATABASE}
      MYSQL_USER: ${DB_USERNAME}
      MYSQL_PASSWORD: ${DB_PASSWORD}
    volumes:
      - mysql_data:/var/lib/mysql
      - ./docker/mysql/production.cnf:/etc/mysql/conf.d/mysql.cnf
    networks:
      - wonderway-network
    command: --default-authentication-plugin=mysql_native_password
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 30s
      timeout: 10s
      retries: 3

  # Redis
  redis:
    image: redis:7-alpine
    container_name: wonderway-redis
    restart: unless-stopped
    command: redis-server --appendonly yes --requirepass ${REDIS_PASSWORD}
    volumes:
      - redis_data:/data
    networks:
      - wonderway-network
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 30s
      timeout: 10s
      retries: 3

  # Queue Workers
  queue:
    build:
      context: .
      dockerfile: Dockerfile.production
    container_name: wonderway-queue
    restart: unless-stopped
    command: php artisan queue:work --sleep=3 --tries=3 --max-time=3600
    environment:
      - APP_ENV=production
    volumes:
      - ./storage:/var/www/html/storage
    networks:
      - wonderway-network
    depends_on:
      - mysql
      - redis

  # Scheduler
  scheduler:
    build:
      context: .
      dockerfile: Dockerfile.production
    container_name: wonderway-scheduler
    restart: unless-stopped
    command: >
      sh -c "while true; do
        php artisan schedule:run --verbose --no-interaction
        sleep 60
      done"
    environment:
      - APP_ENV=production
    volumes:
      - ./storage:/var/www/html/storage
    networks:
      - wonderway-network
    depends_on:
      - mysql
      - redis

  # WebSocket Server
  websocket:
    build:
      context: .
      dockerfile: Dockerfile.production
    container_name: wonderway-websocket
    restart: unless-stopped
    ports:
      - "8080:8080"
    command: php artisan reverb:start --host=0.0.0.0 --port=8080
    environment:
      - APP_ENV=production
    networks:
      - wonderway-network
    depends_on:
      - redis

networks:
  wonderway-network:
    driver: bridge

volumes:
  mysql_data:
    driver: local
  redis_data:
    driver: local
```

### 4. Dockerfile برای Production

```dockerfile
# Dockerfile.production
FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    oniguruma-dev \
    libzip-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    supervisor \
    nginx

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        opcache \
        sockets

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Copy configuration files
COPY docker/php/production.ini /usr/local/etc/php/php.ini
COPY docker/supervisor/production.conf /etc/supervisor/conf.d/supervisord.conf

# Optimize Laravel
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

# Expose port
EXPOSE 9000

# Start supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
```

### 5. اجرای دیپلویمنت

```bash
# ساخت و اجرای containers
docker-compose -f docker-compose.production.yml up -d --build

# بررسی وضعیت containers
docker-compose -f docker-compose.production.yml ps

# اجرای migrations
docker-compose -f docker-compose.production.yml exec app php artisan migrate --force

# ایجاد symbolic link برای storage
docker-compose -f docker-compose.production.yml exec app php artisan storage:link

# بهینهسازی
docker-compose -f docker-compose.production.yml exec app php artisan optimize

# مشاهده لاگها
docker-compose -f docker-compose.production.yml logs -f
```

---

## دیپلویمنت Manual

### 1. نصب PHP و Extensions

```bash
# اضافه کردن repository PHP
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# نصب PHP 8.2
sudo apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-common

# نصب extensions مورد نیاز
sudo apt install -y \
    php8.2-mysql \
    php8.2-mbstring \
    php8.2-xml \
    php8.2-bcmath \
    php8.2-gd \
    php8.2-zip \
    php8.2-curl \
    php8.2-redis \
    php8.2-opcache \
    php8.2-intl

# نصب Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 2. نصب و پیکربندی Nginx

```bash
# نصب Nginx
sudo apt install -y nginx

# پیکربندی Nginx
sudo nano /etc/nginx/sites-available/wonderway
```

#### پیکربندی Nginx

```nginx
server {
    listen 80;
    server_name api.wonderway.com;
    root /var/www/wonderway/public;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private must-revalidate auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

```bash
# فعالسازی سایت
sudo ln -s /etc/nginx/sites-available/wonderway /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 3. نصب MySQL

```bash
# نصب MySQL
sudo apt install -y mysql-server

# امنسازی MySQL
sudo mysql_secure_installation

# ایجاد دیتابیس و کاربر
sudo mysql -u root -p
```

```sql
CREATE DATABASE wonderway CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'wonderway'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON wonderway.* TO 'wonderway'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 4. نصب Redis

```bash
# نصب Redis
sudo apt install -y redis-server

# پیکربندی Redis
sudo nano /etc/redis/redis.conf
```

```conf
# /etc/redis/redis.conf
bind 127.0.0.1
port 6379
requirepass your_secure_password
maxmemory 2gb
maxmemory-policy allkeys-lru
```

```bash
# راهاندازی مجدد Redis
sudo systemctl restart redis-server
sudo systemctl enable redis-server
```

### 5. دیپلوی اپلیکیشن

```bash
# ایجاد دایرکتوری
sudo mkdir -p /var/www/wonderway
cd /var/www/wonderway

# کلون پروژه
sudo git clone https://github.com/your-org/wonderway-backend.git .

# تنظیم مالکیت
sudo chown -R www-data:www-data /var/www/wonderway
sudo chmod -R 755 /var/www/wonderway/storage
sudo chmod -R 755 /var/www/wonderway/bootstrap/cache

# نصب dependencies
composer install --no-dev --optimize-autoloader

# پیکربندی محیط
cp .env.production .env
nano .env

# تولید کلید اپلیکیشن
php artisan key:generate

# اجرای migrations
php artisan migrate --force

# بهینهسازی
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

### 6. پیکربندی Supervisor برای Queue

```bash
# نصب Supervisor
sudo apt install -y supervisor

# پیکربندی Queue Worker
sudo nano /etc/supervisor/conf.d/wonderway-worker.conf
```

```ini
[program:wonderway-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/wonderway/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/wonderway/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
# راهاندازی Supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start wonderway-worker:*
```

### 7. SSL با Let's Encrypt

```bash
# نصب Certbot
sudo apt install -y certbot python3-certbot-nginx

# دریافت گواهی SSL
sudo certbot --nginx -d api.wonderway.com

# تست تمدید خودکار
sudo certbot renew --dry-run
```

---

## Cloud Deployment

### AWS Deployment

#### 1. EC2 Instance Setup

```bash
# Launch EC2 instance
aws ec2 run-instances \
    --image-id ami-0c02fb55956c7d316 \
    --count 1 \
    --instance-type t3.large \
    --key-name wonderway-key \
    --security-group-ids sg-12345678 \
    --subnet-id subnet-12345678 \
    --user-data file://user-data.sh
```

#### 2. RDS Database

```bash
# Create RDS instance
aws rds create-db-instance \
    --db-instance-identifier wonderway-db \
    --db-instance-class db.t3.micro \
    --engine mysql \
    --master-username admin \
    --master-user-password SecurePassword123 \
    --allocated-storage 20 \
    --vpc-security-group-ids sg-12345678 \
    --db-subnet-group-name wonderway-subnet-group
```

#### 3. ElastiCache Redis

```bash
# Create ElastiCache cluster
aws elasticache create-cache-cluster \
    --cache-cluster-id wonderway-redis \
    --cache-node-type cache.t3.micro \
    --engine redis \
    --num-cache-nodes 1 \
    --security-group-ids sg-12345678
```

#### 4. Application Load Balancer

```bash
# Create ALB
aws elbv2 create-load-balancer \
    --name wonderway-alb \
    --subnets subnet-12345678 subnet-87654321 \
    --security-groups sg-12345678
```

### Docker Swarm Deployment

```bash
# Initialize Swarm
docker swarm init

# Deploy stack
docker stack deploy -c docker-compose.swarm.yml wonderway

# Scale services
docker service scale wonderway_app=3
docker service scale wonderway_queue=2
```

### Kubernetes Deployment

#### deployment.yaml

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: wonderway-app
spec:
  replicas: 3
  selector:
    matchLabels:
      app: wonderway-app
  template:
    metadata:
      labels:
        app: wonderway-app
    spec:
      containers:
      - name: app
        image: wonderway/api:latest
        ports:
        - containerPort: 9000
        env:
        - name: APP_ENV
          value: "production"
        - name: DB_HOST
          value: "mysql-service"
        - name: REDIS_HOST
          value: "redis-service"
        resources:
          requests:
            memory: "512Mi"
            cpu: "250m"
          limits:
            memory: "1Gi"
            cpu: "500m"
---
apiVersion: v1
kind: Service
metadata:
  name: wonderway-service
spec:
  selector:
    app: wonderway-app
  ports:
  - port: 80
    targetPort: 9000
  type: LoadBalancer
```

---

## CI/CD Pipeline

### GitHub Actions

#### .github/workflows/deploy.yml

```yaml
name: Deploy to Production

on:
  push:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: wonderway_test
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3
      
      redis:
        image: redis:7-alpine
        options: --health-cmd="redis-cli ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: 8.2
        extensions: mbstring, xml, ctype, iconv, intl, pdo_mysql, dom, filter, gd, iconv, json, mbstring, redis
        
    - name: Cache Composer packages
      id: composer-cache
      uses: actions/cache@v3
      with:
        path: vendor
        key: ${{ runner.os }}-php-${{ hashFiles('**/composer.lock') }}
        restore-keys: |
          ${{ runner.os }}-php-
          
    - name: Install dependencies
      run: composer install --prefer-dist --no-progress
      
    - name: Copy environment file
      run: cp .env.testing .env
      
    - name: Generate application key
      run: php artisan key:generate
      
    - name: Run migrations
      run: php artisan migrate --force
      
    - name: Run tests
      run: php artisan test --coverage --min=80

  security-scan:
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v3
    
    - name: Run security audit
      run: composer audit
      
    - name: Run static analysis
      run: ./vendor/bin/phpstan analyse

  deploy:
    needs: [test, security-scan]
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup SSH
      uses: webfactory/ssh-agent@v0.7.0
      with:
        ssh-private-key: ${{ secrets.SSH_PRIVATE_KEY }}
        
    - name: Deploy to server
      run: |
        ssh -o StrictHostKeyChecking=no ${{ secrets.SSH_USER }}@${{ secrets.SSH_HOST }} << 'EOF'
          cd /var/www/wonderway
          git pull origin main
          composer install --no-dev --optimize-autoloader
          php artisan migrate --force
          php artisan config:cache
          php artisan route:cache
          php artisan view:cache
          sudo supervisorctl restart wonderway-worker:*
          sudo systemctl reload nginx
        EOF
        
    - name: Health check
      run: |
        sleep 30
        curl -f ${{ secrets.APP_URL }}/health || exit 1
        
    - name: Notify deployment
      uses: 8398a7/action-slack@v3
      with:
        status: ${{ job.status }}
        channel: '#deployments'
        webhook_url: ${{ secrets.SLACK_WEBHOOK }}
```

### GitLab CI/CD

#### .gitlab-ci.yml

```yaml
stages:
  - test
  - security
  - build
  - deploy

variables:
  MYSQL_ROOT_PASSWORD: password
  MYSQL_DATABASE: wonderway_test

test:
  stage: test
  image: php:8.2
  services:
    - mysql:8.0
    - redis:7-alpine
  before_script:
    - apt-get update -qq && apt-get install -y -qq git curl libmcrypt-dev libjpeg-dev libpng-dev libfreetype6-dev libbz2-dev libzip-dev
    - docker-php-ext-install pdo_mysql zip gd
    - curl -sS https://getcomposer.org/installer | php
    - php composer.phar install --no-dev --no-scripts
  script:
    - cp .env.testing .env
    - php artisan key:generate
    - php artisan migrate --force
    - php artisan test

security:
  stage: security
  image: php:8.2
  script:
    - composer audit
    - ./vendor/bin/phpstan analyse

build:
  stage: build
  image: docker:latest
  services:
    - docker:dind
  script:
    - docker build -t $CI_REGISTRY_IMAGE:$CI_COMMIT_SHA .
    - docker push $CI_REGISTRY_IMAGE:$CI_COMMIT_SHA

deploy:
  stage: deploy
  image: alpine:latest
  before_script:
    - apk add --no-cache openssh-client
    - eval $(ssh-agent -s)
    - echo "$SSH_PRIVATE_KEY" | tr -d '\r' | ssh-add -
  script:
    - ssh -o StrictHostKeyChecking=no $SSH_USER@$SSH_HOST "
        cd /var/www/wonderway &&
        git pull origin main &&
        composer install --no-dev --optimize-autoloader &&
        php artisan migrate --force &&
        php artisan optimize &&
        sudo supervisorctl restart wonderway-worker:*
      "
  only:
    - main
```

---

## مانیتورینگ و لاگگیری

### Prometheus و Grafana

#### docker-compose.monitoring.yml

```yaml
version: '3.8'

services:
  prometheus:
    image: prom/prometheus
    container_name: prometheus
    ports:
      - "9090:9090"
    volumes:
      - ./monitoring/prometheus.yml:/etc/prometheus/prometheus.yml
      - prometheus_data:/prometheus
    command:
      - '--config.file=/etc/prometheus/prometheus.yml'
      - '--storage.tsdb.path=/prometheus'
      - '--web.console.libraries=/etc/prometheus/console_libraries'
      - '--web.console.templates=/etc/prometheus/consoles'

  grafana:
    image: grafana/grafana
    container_name: grafana
    ports:
      - "3000:3000"
    environment:
      - GF_SECURITY_ADMIN_PASSWORD=admin123
    volumes:
      - grafana_data:/var/lib/grafana
      - ./monitoring/grafana/dashboards:/etc/grafana/provisioning/dashboards
      - ./monitoring/grafana/datasources:/etc/grafana/provisioning/datasources

  node-exporter:
    image: prom/node-exporter
    container_name: node-exporter
    ports:
      - "9100:9100"
    volumes:
      - /proc:/host/proc:ro
      - /sys:/host/sys:ro
      - /:/rootfs:ro
    command:
      - '--path.procfs=/host/proc'
      - '--path.rootfs=/rootfs'
      - '--path.sysfs=/host/sys'
      - '--collector.filesystem.mount-points-exclude=^/(sys|proc|dev|host|etc)($$|/)'

volumes:
  prometheus_data:
  grafana_data:
```

### ELK Stack برای لاگگیری

#### docker-compose.elk.yml

```yaml
version: '3.8'

services:
  elasticsearch:
    image: docker.elastic.co/elasticsearch/elasticsearch:8.11.0
    container_name: elasticsearch
    environment:
      - discovery.type=single-node
      - xpack.security.enabled=false
      - "ES_JAVA_OPTS=-Xms512m -Xmx512m"
    ports:
      - "9200:9200"
    volumes:
      - elasticsearch_data:/usr/share/elasticsearch/data

  logstash:
    image: docker.elastic.co/logstash/logstash:8.11.0
    container_name: logstash
    volumes:
      - ./elk/logstash.conf:/usr/share/logstash/pipeline/logstash.conf
    ports:
      - "5044:5044"
    depends_on:
      - elasticsearch

  kibana:
    image: docker.elastic.co/kibana/kibana:8.11.0
    container_name: kibana
    ports:
      - "5601:5601"
    environment:
      - ELASTICSEARCH_HOSTS=http://elasticsearch:9200
    depends_on:
      - elasticsearch

volumes:
  elasticsearch_data:
```

---

## بکاپ و بازیابی

### اسکریپت بکاپ خودکار

```bash
#!/bin/bash
# backup.sh

# تنظیمات
BACKUP_DIR="/backups/wonderway"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="wonderway"
DB_USER="wonderway"
DB_PASS="password"

# ایجاد دایرکتوری بکاپ
mkdir -p $BACKUP_DIR/$DATE

# بکاپ دیتابیس
mysqldump -u$DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/$DATE/database.sql

# بکاپ فایلها
tar -czf $BACKUP_DIR/$DATE/files.tar.gz /var/www/wonderway/storage

# بکاپ Redis
redis-cli --rdb $BACKUP_DIR/$DATE/redis.rdb

# فشردهسازی
tar -czf $BACKUP_DIR/wonderway_backup_$DATE.tar.gz -C $BACKUP_DIR/$DATE .

# حذف فایلهای موقت
rm -rf $BACKUP_DIR/$DATE

# آپلود به S3 (اختیاری)
aws s3 cp $BACKUP_DIR/wonderway_backup_$DATE.tar.gz s3://wonderway-backups/

# حذف بکاپهای قدیمی (بیش از 30 روز)
find $BACKUP_DIR -name "wonderway_backup_*.tar.gz" -mtime +30 -delete

echo "Backup completed: wonderway_backup_$DATE.tar.gz"
```

### اسکریپت بازیابی

```bash
#!/bin/bash
# restore.sh

BACKUP_FILE=$1
TEMP_DIR="/tmp/wonderway_restore"

if [ -z "$BACKUP_FILE" ]; then
    echo "Usage: $0 <backup_file>"
    exit 1
fi

# استخراج بکاپ
mkdir -p $TEMP_DIR
tar -xzf $BACKUP_FILE -C $TEMP_DIR

# بازیابی دیتابیس
mysql -u wonderway -p wonderway < $TEMP_DIR/database.sql

# بازیابی فایلها
tar -xzf $TEMP_DIR/files.tar.gz -C /

# بازیابی Redis
redis-cli --rdb $TEMP_DIR/redis.rdb

# پاکسازی
rm -rf $TEMP_DIR

echo "Restore completed from $BACKUP_FILE"
```

### Cron Job برای بکاپ خودکار

```bash
# اضافه کردن به crontab
crontab -e

# بکاپ روزانه در ساعت 2 شب
0 2 * * * /path/to/backup.sh

# بکاپ هفتگی در یکشنبه ساعت 1 شب
0 1 * * 0 /path/to/weekly_backup.sh
```

---

## عیبیابی

### مشکلات رایج و راهحلها

#### 1. خطای 500 Internal Server Error

```bash
# بررسی لاگهای Nginx
sudo tail -f /var/log/nginx/error.log

# بررسی لاگهای PHP-FPM
sudo tail -f /var/log/php8.2-fpm.log

# بررسی لاگهای Laravel
tail -f /var/www/wonderway/storage/logs/laravel.log

# بررسی مجوزها
sudo chown -R www-data:www-data /var/www/wonderway
sudo chmod -R 755 /var/www/wonderway/storage
```

#### 2. مشکل اتصال به دیتابیس

```bash
# تست اتصال MySQL
mysql -u wonderway -p -h localhost wonderway

# بررسی وضعیت MySQL
sudo systemctl status mysql

# بررسی پیکربندی
cat /var/www/wonderway/.env | grep DB_
```

#### 3. مشکل Queue Workers

```bash
# بررسی وضعیت Supervisor
sudo supervisorctl status

# راهاندازی مجدد Workers
sudo supervisorctl restart wonderway-worker:*

# بررسی لاگهای Worker
tail -f /var/www/wonderway/storage/logs/worker.log
```

#### 4. مشکل Redis

```bash
# تست اتصال Redis
redis-cli ping

# بررسی وضعیت Redis
sudo systemctl status redis-server

# مانیتورینگ Redis
redis-cli monitor
```

### ابزارهای مانیتورینگ

#### Health Check Script

```bash
#!/bin/bash
# health_check.sh

# بررسی وضعیت سرویسها
services=("nginx" "php8.2-fpm" "mysql" "redis-server" "supervisor")

for service in "${services[@]}"; do
    if systemctl is-active --quiet $service; then
        echo "✅ $service is running"
    else
        echo "❌ $service is not running"
        sudo systemctl start $service
    fi
done

# بررسی دسترسی به اپلیکیشن
if curl -f -s http://localhost/health > /dev/null; then
    echo "✅ Application is responding"
else
    echo "❌ Application is not responding"
fi

# بررسی فضای دیسک
disk_usage=$(df / | awk 'NR==2 {print $5}' | sed 's/%//')
if [ $disk_usage -gt 80 ]; then
    echo "⚠️  Disk usage is high: ${disk_usage}%"
fi

# بررسی استفاده از RAM
mem_usage=$(free | awk 'NR==2{printf "%.0f", $3*100/$2}')
if [ $mem_usage -gt 80 ]; then
    echo "⚠️  Memory usage is high: ${mem_usage}%"
fi
```

### Performance Tuning

#### PHP-FPM Optimization

```ini
; /etc/php/8.2/fpm/pool.d/www.conf
[www]
user = www-data
group = www-data
listen = /var/run/php/php8.2-fpm.sock
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.process_idle_timeout = 10s
pm.max_requests = 500
```

#### MySQL Optimization

```ini
# /etc/mysql/mysql.conf.d/mysqld.cnf
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
query_cache_type = 1
query_cache_size = 256M
max_connections = 200
thread_cache_size = 16
table_open_cache = 4000
```

#### Redis Optimization

```conf
# /etc/redis/redis.conf
maxmemory 2gb
maxmemory-policy allkeys-lru
tcp-keepalive 300
timeout 0
tcp-backlog 511
save 900 1
save 300 10
save 60 10000
```

---

## خلاصه

### Checklist دیپلویمنت

#### قبل از دیپلویمنت
- [ ] تست کامل اپلیکیشن
- [ ] بررسی امنیتی
- [ ] آمادهسازی محیط production
- [ ] پیکربندی monitoring
- [ ] تنظیم بکاپ

#### حین دیپلویمنت
- [ ] اجرای migrations
- [ ] بهینهسازی Laravel
- [ ] تست health checks
- [ ] بررسی لاگها
- [ ] تست عملکرد

#### بعد از دیپلویمنت
- [ ] مانیتورینگ مداوم
- [ ] بررسی metrics
- [ ] تست load
- [ ] بروزرسانی مستندات
- [ ] آموزش تیم

### بهترین شیوهها

1. **استفاده از Infrastructure as Code**
2. **پیادهسازی CI/CD pipeline**
3. **مانیتورینگ و alerting**
4. **بکاپ منظم و تست بازیابی**
5. **Security hardening**
6. **Performance optimization**
7. **Documentation و runbooks**

دیپلویمنت موفق نیازمند برنامهریزی دقیق، تست کامل و مانیتورینگ مداوم است.