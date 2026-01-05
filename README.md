# Microblogging Platform

A modern, enterprise-grade microblogging platform built with Laravel 12, featuring advanced authentication, real-time capabilities, comprehensive social media functionality, and monetization systems.

## 🚀 Features

### Authentication & Security
- **Multi-step Registration**: Email/Phone verification with OTP
- **Social Authentication**: Google, Apple integration
- **Two-Factor Authentication**: Google Authenticator with backup codes
- **Advanced Security**: Brute force protection, device fingerprinting, session management
- **Password Security**: History tracking, strength validation, secure reset
- **Real-time Monitoring**: Security audit system with threat detection

### Social Features
- **Posts & Content**: Text posts (280 chars), media upload, drafts, scheduled posts
- **Interactions**: Like, comment, repost, quote tweets
- **Social Graph**: Follow/unfollow, follow requests for private accounts
- **Mentions & Hashtags**: User mentions, trending hashtags
- **Bookmarks**: Save posts for later
- **Lists & Spaces**: User lists, audio spaces
- **Direct Messaging**: Real-time private messaging
- **Community Notes**: Collaborative fact-checking

### Content & Media
- **Media Upload**: Images, videos, documents with CDN integration
- **Image Processing**: Automatic optimization, thumbnail generation
- **File Management**: AWS S3 storage with CloudFront CDN
- **Content Moderation**: Spam detection, reporting system, auto-moderation

### Real-time Features
- **Live Timeline**: Real-time post updates
- **Notifications**: Push notifications, in-app notifications
- **Messaging**: Real-time chat with typing indicators
- **Online Status**: User presence tracking
- **Broadcasting**: Laravel Reverb WebSocket integration

### Monetization & Business
- **Subscriptions**: Free, Premium, Creator tiers
- **Advertisement System**: Targeted ads with analytics
- **Creator Fund**: Revenue sharing for content creators
- **Analytics**: Comprehensive user and content analytics
- **A/B Testing**: Feature testing and optimization

### Advanced Features
- **Search**: Full-text search with Elasticsearch/Meilisearch
- **Trending**: Real-time trending content and hashtags
- **Polls**: Interactive polls with voting
- **Moments**: Curated content collections
- **Parental Controls**: Child account management
- **Localization**: Multi-language support (English, Persian, Arabic, etc.)

## 🛠 Tech Stack

### Backend
- **Framework**: Laravel 12.x
- **PHP**: 8.2+
- **Database**: MySQL 8.0+
- **Cache**: Redis 7.0+
- **Queue**: Redis with Horizon
- **Search**: Elasticsearch/Meilisearch
- **WebSockets**: Laravel Reverb

### Authentication & Security
- **API Authentication**: Laravel Sanctum + JWT
- **2FA**: Google2FA
- **Permissions**: Spatie Laravel Permission
- **Rate Limiting**: Advanced Redis-based limiting
- **Security**: WAF, Input validation, XSS protection

### Infrastructure
- **File Storage**: AWS S3
- **CDN**: CloudFront
- **Monitoring**: Custom performance monitoring
- **Admin Panel**: Filament 3.x
- **API Documentation**: L5-Swagger (OpenAPI)

### Development & Testing
- **Testing**: PHPUnit with 98% test coverage
- **Code Quality**: PHP CS Fixer, Laravel Pint
- **CI/CD**: GitHub Actions
- **Docker**: Production-ready containers
- **Deployment**: Docker Compose with Nginx

## 📦 Installation

### Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8.0+
- Redis 7.0+

### Quick Start
```bash
# Clone repository
git clone <repository-url>
cd backend

# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database setup
php artisan migrate
php artisan db:seed

# Start services
php artisan serve
php artisan queue:work
php artisan reverb:start
```

### Docker Setup
```bash
# Start all services
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate

# Access application
http://localhost:8080
```

## 🔧 Configuration

### Environment Variables
```env
# Application
APP_NAME="Microblogging"
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=microblogging

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# AWS S3
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket

# Social Auth
GOOGLE_CLIENT_ID=your-google-client-id
APPLE_CLIENT_ID=your-apple-client-id
```

## 🎯 Management Commands

### Enterprise Management
```bash
# System status
php artisan microblogging:enterprise status

# Process creator payments
php artisan microblogging:enterprise process-creator-payments

# Generate analytics
php artisan microblogging:enterprise generate-analytics
```

### Performance & Monitoring
```bash
# Cache warmup
php artisan cache:warmup

# Performance monitoring
php artisan performance:monitor

# Security audit
php artisan security:audit

# Database optimization
php artisan db:optimize
```

### Content Management
```bash
# Update trending data
php artisan trending:update

# Publish scheduled posts
php artisan posts:publish-scheduled

# Search system analysis
php artisan search:analyze
```

## 📊 API Documentation

### Access Points
- **Swagger UI**: `/api/documentation`
- **JSON Spec**: `/api/documentation.json`
- **Postman Collection**: Available in `/docs` folder

### Key Endpoints
- **Authentication**: `/api/auth/*`
- **Posts**: `/api/posts/*`
- **Users**: `/api/users/*`
- **Timeline**: `/api/timeline`
- **Search**: `/api/search`
- **Notifications**: `/api/notifications`

## 🔒 Security Features

### Authentication Security
- Multi-factor authentication (2FA)
- Device fingerprinting and verification
- Session management with device tracking
- Password history and strength validation
- Brute force protection with IP blocking

### Application Security
- Advanced input validation and sanitization
- XSS and SQL injection protection
- CSRF protection
- Rate limiting with Redis
- Real-time security monitoring
- Automated threat detection

### Data Protection
- Encrypted sensitive data
- Secure file upload validation
- Content moderation and spam detection
- Privacy controls and data anonymization

## 📈 Performance & Scalability

### Caching Strategy
- Redis for session and application cache
- Database query optimization
- CDN integration for static assets
- Eager loading and query optimization

### Monitoring & Analytics
- Real-time performance monitoring
- User behavior analytics
- A/B testing framework
- Error tracking and logging
- Custom metrics and dashboards

## 🧪 Testing

### Test Coverage
- **Unit Tests**: 98% coverage
- **Feature Tests**: Complete API coverage
- **Integration Tests**: End-to-end workflows
- **Performance Tests**: Load and stress testing

### Running Tests
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

## 🚀 Deployment

### Production Checklist
- [ ] Environment variables configured
- [ ] Database migrations run
- [ ] Redis configured and running
- [ ] File storage (S3) configured
- [ ] CDN configured
- [ ] SSL certificates installed
- [ ] Monitoring and logging setup
- [ ] Backup strategy implemented

### Docker Production
```bash
# Build production image
docker build -t microblogging:latest .

# Deploy with docker-compose
docker-compose -f docker-compose.prod.yml up -d
```

## 🤝 Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

### Development Standards
- Follow PSR-12 coding standards
- Write comprehensive tests
- Update documentation
- Use conventional commit messages

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

- **Documentation**: [Wiki](../../wiki)
- **Issues**: [GitHub Issues](../../issues)
- **Discussions**: [GitHub Discussions](../../discussions)

---

**Built with ❤️ using Laravel 12 and modern web technologies**