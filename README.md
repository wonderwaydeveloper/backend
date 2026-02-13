# Microblogging Platform

A modern microblogging platform built with Laravel 12, featuring advanced authentication, real-time capabilities, and comprehensive social media functionality.

## 🚀 Features

### Authentication & Security
- **Multi-step Registration**: 3-step registration with email/phone verification (OTP)
- **Social Authentication**: Google OAuth integration
- **Two-Factor Authentication**: Google Authenticator (2FA)
- **Device Management**: Device fingerprinting, verification, and trust system
- **Session Management**: Multi-device session tracking and revocation
- **Password Security**: Secure reset with OTP, password change
- **Security Monitoring**: Audit logs, security events, anomaly detection

### Social Features
- **Posts**: Create, edit, delete posts with 280 character limit
- **Drafts**: Save posts as drafts before publishing
- **Scheduled Posts**: Schedule posts for future publishing
- **Threads**: Create multi-post threads
- **Interactions**: Like, comment, repost, quote posts
- **Follow System**: Follow/unfollow users, follow requests for private accounts
- **Mentions & Hashtags**: @mentions and #hashtags with trending support
- **Bookmarks**: Save posts for later
- **Direct Messaging**: Real-time private messaging with typing indicators
- **Community Notes**: Collaborative fact-checking with voting system

### Communities
- **Create Communities**: Public/private communities
- **Community Posts**: Post within communities
- **Member Management**: Join requests, approve/reject members
- **Roles & Permissions**: Community admin and member roles

### Content & Media
- **Media Upload**: Images, videos, documents
- **GIF Integration**: Giphy API integration for GIF search
- **Video Processing**: Background video processing with status tracking
- **Content Moderation**: User reporting system

### Real-time Features
- **Live Timeline**: Real-time post updates via WebSocket
- **Notifications**: In-app notifications with preferences
- **Push Notifications**: Device-based push notifications
- **Messaging**: Real-time chat with typing indicators
- **Online Status**: User presence tracking
- **Broadcasting**: Laravel Reverb WebSocket integration

### Advanced Features
- **Search & Discovery System**: Full-text search with MeiliSearch
  - **Search Posts**: Search through posts with filters (date, media, user, hashtags)
  - **Search Users**: Find users by username, name, bio
  - **Search Hashtags**: Discover trending and popular hashtags
  - **Advanced Search**: Multi-criteria search with sorting options
  - **Suggestions**: Smart search suggestions and autocomplete
  - **Rate Limiting**: Twitter API v2 compliant (450/15min posts, 180/15min users)
  - **Block/Mute Integration**: Filtered results excluding blocked/muted users
  - **Real-time Indexing**: Automatic content indexing via events and jobs
- **Trending System**: Real-time trending content
  - **Trending Hashtags**: Top hashtags with engagement scoring
  - **Trending Posts**: Viral posts with time decay algorithm
  - **Trending Users**: Popular users based on follower growth
  - **Personalized Trending**: User-specific trending content
  - **Trend Velocity**: Track trending speed and momentum
  - **Cache Optimization**: 15-minute TTL for performance
- **Spaces**: Audio rooms with participant management
- **Lists**: Create and manage user lists
- **Polls**: Create polls with multiple options and voting
- **Moments**: Curated content collections
- **User Suggestions**: Personalized user recommendations

### Monetization
- **Premium Subscriptions**: Multi-tier subscription plans
- **Advertisement System**: Targeted ads with click tracking and analytics
- **Creator Fund**: Revenue sharing for content creators with earnings tracking

### Analytics & Monitoring
- **User Analytics**: User activity and engagement metrics
- **Post Analytics**: Individual post performance tracking
- **Conversion Tracking**: Funnel analysis, cohort analysis, user journey
- **A/B Testing**: Feature testing with variant assignment and event tracking
- **Performance Monitoring**: System status, cache, queue monitoring
- **Auto-scaling**: Predictive scaling based on metrics

## 🛠 Tech Stack

### Backend
- **Framework**: Laravel 12.x
- **PHP**: 8.2+
- **Database**: MySQL 8.0+
- **Cache**: Redis
- **Queue**: Redis
- **Search**: Meilisearch
- **WebSockets**: Laravel Reverb

### Key Packages
- **API Authentication**: Laravel Sanctum
- **2FA**: pragmarx/google2fa-laravel
- **Permissions**: Spatie Laravel Permission
- **Search**: Laravel Scout + Meilisearch
- **Social Auth**: Laravel Socialite
- **SMS**: Twilio SDK
- **Image Processing**: Intervention Image
- **Admin Panel**: Filament 4.x
- **API Documentation**: L5-Swagger (OpenAPI)

### Development & Testing
- **Testing**: PHPUnit
- **Code Quality**: PHP CS Fixer, Laravel Pint
- **Docker**: Docker Compose setup available

## 📦 Installation

### Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8.0+
- Redis
- Meilisearch (optional, for search)

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
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=microblogging
DB_USERNAME=root
DB_PASSWORD=

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=

# Twilio (SMS)
TWILIO_SID=your-twilio-sid
TWILIO_TOKEN=your-twilio-token
TWILIO_FROM=your-twilio-phone

# Google OAuth
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URI=http://localhost:8000/api/auth/social/google/callback

# Giphy (optional)
GIPHY_API_KEY=DEMO_API_KEY

# SendGrid (optional)
SENDGRID_API_KEY=
SENDGRID_FROM_EMAIL=

# Firebase (optional, for push notifications)
FIREBASE_API_KEY=
FIREBASE_PROJECT_ID=
FIREBASE_CREDENTIALS_PATH=
```

## 🎯 Artisan Commands

### Content Management
```bash
# Publish scheduled posts
php artisan posts:publish-scheduled

# Update trending data
php artisan trending:update

# Reindex search content
php artisan scout:import "App\Models\Post"
php artisan scout:import "App\Models\User"
```

### Maintenance
```bash
# Cache warmup
php artisan cache:warmup

# Database optimization
php artisan db:optimize

# Performance monitoring
php artisan performance:monitor

# Security audit
php artisan security:audit

# Cleanup audit logs
php artisan audit:cleanup

# Cleanup expired tokens
php artisan tokens:cleanup
```

### Analysis
```bash
# Architecture analysis
php artisan architecture:analyze

# Code quality check
php artisan code:quality-check

# Search system analysis
php artisan search:analyze

# Project cleanup analysis
php artisan project:cleanup-analysis
```

## 📊 API Documentation

### Access Points
- **Swagger UI**: `/api/documentation`
- **JSON Spec**: `/api/documentation.json`
- **Health Check**: `/api/health`

### Key Endpoint Groups
- **Authentication**: `/api/auth/*` (login, register, 2FA, password reset, sessions)
- **Posts**: `/api/posts/*` (CRUD, like, quote, drafts, scheduled)
- **Comments**: `/api/posts/{post}/comments`
- **Users**: `/api/users/*` (profile, followers, following)
- **Follow**: `/api/users/{user}/follow`, `/api/follow-requests`
- **Timeline**: `/api/timeline`, `/api/optimized/timeline`
- **Search & Discovery**: 
  - `/api/search/posts` - Search posts (450 req/15min)
  - `/api/search/users` - Search users (180 req/15min)
  - `/api/search/hashtags` - Search hashtags (180 req/15min)
  - `/api/search/advanced` - Advanced multi-criteria search
  - `/api/search/suggestions` - Search suggestions (180 req/15min)
  - `/api/trending/hashtags` - Trending hashtags (75 req/15min)
  - `/api/trending/posts` - Trending posts (75 req/15min)
  - `/api/trending/users` - Trending users (75 req/15min)
  - `/api/trending/personalized` - Personalized trending (180 req/15min)
  - `/api/trending/velocity` - Trend velocity (180 req/15min)
  - `/api/trending/stats` - Trending statistics (180 req/15min)
- **Messages**: `/api/messages/*` (conversations, send, typing)
- **Notifications**: `/api/notifications/*`
- **Bookmarks**: `/api/bookmarks`
- **Hashtags**: `/api/hashtags/*`
- **Trending**: `/api/trending/*`
- **Spaces**: `/api/spaces/*`
- **Lists**: `/api/lists/*`
- **Polls**: `/api/polls/*`
- **Moments**: `/api/moments/*`
- **Communities**: `/api/communities/*`
- **Monetization**: `/api/monetization/*` (ads, creator-fund, premium)
- **Analytics**: `/api/analytics/*`

## 🔒 Security Features

### Authentication Security
- Multi-step registration with OTP verification
- Two-factor authentication (2FA) with Google Authenticator
- Device fingerprinting and verification
- Session management with multi-device tracking
- Secure password reset with OTP
- Social authentication (Google OAuth)

### Application Security
- Input validation and sanitization
- Rate limiting on sensitive endpoints
- CSRF protection
- Security middleware (UnifiedSecurityMiddleware)
- Audit logging for all actions
- Security event tracking
- Anomaly detection

### Data Protection
- Secure file upload validation
- Content moderation (user reporting)
- Privacy settings (private accounts)
- Block/mute functionality

## 📈 Performance & Monitoring

### Caching
- Redis for session and application cache
- Timeline caching
- Query optimization with eager loading

### Monitoring
- Performance monitoring dashboard
- Cache monitoring
- Queue monitoring
- System status tracking
- Auto-scaling metrics

## 🧪 Testing

### Running Tests
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run with coverage
php artisan test --coverage

# Run Search & Discovery System tests
php test_search_discovery_system.php
```

### Test Coverage
- **Search & Discovery**: 175 comprehensive tests
  - System Review: 68 tests (Architecture, Database, API, Security, Validation, Business Logic, Integration, Testing)
  - Twitter Compliance: 26 tests (Rate limits, Query parameters, Pagination, Features, Filters, Security)
  - Operational Readiness: 48 tests (No parallel work, Components, Routes, Database, Security, Integration)
  - Final Verification: 20 tests (Core files, Request classes, Resources, Events, Listeners, Jobs, Tests)
  - Cleanliness: 13 tests (No unused files, No duplicates, No debug code, Clean configuration)

## 🚀 Deployment

### Production Checklist
- [ ] Environment variables configured
- [ ] Database migrations run
- [ ] Redis configured and running
- [ ] Meilisearch configured (optional)
- [ ] Queue worker running
- [ ] Reverb WebSocket server running
- [ ] SSL certificates installed
- [ ] Scheduled tasks configured (cron)

### Docker Production
```bash
# Build production image
docker build -t microblogging:latest .

# Deploy with docker-compose
docker-compose up -d
```

### Scheduled Tasks
Add to crontab:
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

## 📁 Project Structure

```
app/
├── Console/Commands/     # Artisan commands
├── Contracts/           # Interfaces
├── DTOs/                # Data Transfer Objects
├── Events/              # Event classes
├── Exceptions/          # Custom exceptions
├── Filament/            # Admin panel resources
├── Http/
│   ├── Controllers/Api/ # API controllers
│   ├── Middleware/      # Custom middleware
│   ├── Requests/        # Form requests
│   └── Resources/       # API resources
├── Jobs/                # Queue jobs
├── Listeners/           # Event listeners
├── Mail/                # Mail classes
├── Models/              # Eloquent models
├── Monetization/        # Monetization features
├── Notifications/       # Notification classes
├── Observers/           # Model observers
├── Policies/            # Authorization policies
├── Providers/           # Service providers
├── Repositories/        # Repository pattern
├── Rules/               # Validation rules
├── Services/            # Business logic services
└── Traits/              # Reusable traits
```

## 📄 License

This project is licensed under the MIT License.

---

**Built with Laravel 12**