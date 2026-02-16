# Clevlance - System Architecture

## 📐 Overview

Clevlance is built using a modern, scalable architecture designed to handle millions of users with real-time interactions.

## 🏗️ Architecture Layers

```
┌─────────────────────────────────────────────────────────┐
│                    Client Layer                          │
│  (Web App, Mobile App, Third-party Integrations)        │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│                    API Gateway                           │
│         (Nginx, Rate Limiting, Load Balancer)           │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│                 Application Layer                        │
│              (Laravel 12 Backend)                        │
│  ┌──────────────┬──────────────┬──────────────┐        │
│  │ Controllers  │  Services    │  Repositories │        │
│  └──────────────┴──────────────┴──────────────┘        │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│                   Data Layer                             │
│  ┌──────────┬──────────┬──────────┬──────────┐         │
│  │  MySQL   │  Redis   │ Meilisearch│ S3/Local│         │
│  └──────────┴──────────┴──────────┴──────────┘         │
└─────────────────────────────────────────────────────────┘
```

## 🎯 Design Patterns

### 1. Repository Pattern
Abstracts data access logic from business logic.

```php
interface PostRepositoryInterface
{
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
}

class PostRepository implements PostRepositoryInterface
{
    // Implementation
}
```

### 2. Service Layer Pattern
Encapsulates business logic.

```php
class PostService
{
    public function __construct(
        private PostRepository $postRepository,
        private CacheService $cacheService
    ) {}

    public function getPublicPosts()
    {
        return $this->cacheService->remember('posts.public', function() {
            return $this->postRepository->getPublished();
        });
    }
}
```

### 3. Observer Pattern
For event-driven architecture.

```php
// Events
PostCreated::class
PostLiked::class
UserFollowed::class

// Listeners
SendNotification::class
UpdateCache::class
TrackAnalytics::class
```

### 4. Strategy Pattern
For flexible algorithms (e.g., trending calculation).

```php
interface TrendingStrategyInterface
{
    public function calculate($data);
}

class VelocityTrendingStrategy implements TrendingStrategyInterface
{
    public function calculate($data)
    {
        // Velocity-based algorithm
    }
}
```

## 🗄️ Database Architecture

### Schema Design

**Users Table**
```sql
users
├── id (PK)
├── name
├── username (UNIQUE)
├── email (UNIQUE)
├── password
├── avatar
├── bio
├── is_verified
├── is_premium
├── followers_count
├── following_count
├── posts_count
└── timestamps
```

**Posts Table**
```sql
posts
├── id (PK)
├── user_id (FK)
├── content
├── visibility
├── reply_settings
├── published_at
├── likes_count
├── comments_count
├── reposts_count
├── views_count
├── thread_id (FK, nullable)
├── quoted_post_id (FK, nullable)
└── timestamps
```

**Relationships**
- Users → Posts (1:N)
- Posts → Comments (1:N)
- Posts → Likes (N:M)
- Users → Followers (N:M)
- Posts → Hashtags (N:M)
- Users → Messages (N:M)

### Indexing Strategy

```sql
-- Performance indexes
CREATE INDEX idx_posts_user_published ON posts(user_id, published_at);
CREATE INDEX idx_posts_published ON posts(published_at);
CREATE INDEX idx_hashtags_name ON hashtags(name);
CREATE INDEX idx_follows_follower ON follows(follower_id, created_at);
CREATE INDEX idx_follows_following ON follows(following_id, created_at);

-- Full-text search
CREATE FULLTEXT INDEX idx_posts_content ON posts(content);
CREATE FULLTEXT INDEX idx_users_search ON users(name, username, bio);
```

## 🚀 Caching Strategy

### Cache Layers

**1. Application Cache (Redis)**
```php
// User profile cache
Cache::remember("user:{$userId}", 600, fn() => User::find($userId));

// Timeline cache
Cache::remember("timeline:{$userId}:page:{$page}", 300, fn() => 
    $this->getTimelinePosts($userId, $page)
);

// Trending cache
Cache::remember("trending:hashtags", 900, fn() => 
    $this->calculateTrendingHashtags()
);
```

**2. Query Result Cache**
```php
// Eloquent query caching
Post::published()
    ->with('user')
    ->remember(1800)
    ->get();
```

**3. HTTP Cache**
```php
// Response caching
return response()->json($data)
    ->header('Cache-Control', 'public, max-age=300');
```

### Cache Invalidation

```php
// Event-based invalidation
class PostCreated
{
    public function handle()
    {
        Cache::tags(['posts', "user:{$this->post->user_id}"])->flush();
    }
}
```

## 🔄 Queue Architecture

### Queue Workers

```php
// Job types
ProcessVideoJob::class       // High priority
SendEmailJob::class          // Medium priority
UpdateAnalyticsJob::class    // Low priority
GenerateThumbnailJob::class  // Low priority
```

### Queue Configuration

```php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
    ],
],
```

## 🔍 Search Architecture

### Meilisearch Integration

```php
// Searchable models
class Post extends Model
{
    use Searchable;

    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'user_name' => $this->user->name,
            'hashtags' => $this->hashtags->pluck('name'),
            'published_at' => $this->published_at->timestamp,
        ];
    }
}
```

### Search Ranking

```php
// Custom ranking rules
'rankingRules' => [
    'words',
    'typo',
    'proximity',
    'attribute',
    'sort',
    'exactness',
    'published_at:desc',
    'likes_count:desc',
]
```

## 🔐 Security Architecture

### Authentication Flow

```
1. User Login
   ↓
2. Validate Credentials
   ↓
3. Check 2FA (if enabled)
   ↓
4. Generate Sanctum Token
   ↓
5. Return Token + User Data
```

### Authorization Layers

**1. Middleware**
```php
Route::middleware(['auth:sanctum', 'security:api'])
    ->group(function() {
        // Protected routes
    });
```

**2. Policies**
```php
class PostPolicy
{
    public function update(User $user, Post $post)
    {
        return $user->id === $post->user_id;
    }
}
```

**3. Gates**
```php
Gate::define('admin-access', function (User $user) {
    return $user->hasRole('admin');
});
```

### Security Headers

```php
// Applied globally
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Strict-Transport-Security: max-age=31536000
Content-Security-Policy: default-src 'self'
```

## 📡 Real-time Architecture

### Laravel Reverb (WebSocket)

```php
// Broadcasting configuration
'reverb' => [
    'driver' => 'reverb',
    'key' => env('REVERB_APP_KEY'),
    'secret' => env('REVERB_APP_SECRET'),
    'app_id' => env('REVERB_APP_ID'),
    'options' => [
        'host' => env('REVERB_HOST', '0.0.0.0'),
        'port' => env('REVERB_PORT', 8080),
        'scheme' => env('REVERB_SCHEME', 'http'),
    ],
],
```

### Event Broadcasting

```php
// Private channel
broadcast(new NewMessage($message))->toOthers();

// Presence channel
broadcast(new UserOnline($user));
```

### Client Integration

```javascript
// Laravel Echo
Echo.private(`user.${userId}`)
    .listen('NewMessage', (e) => {
        // Handle new message
    })
    .listen('NewNotification', (e) => {
        // Handle notification
    });
```

## 📊 Analytics Architecture

### Data Collection

```php
// Event tracking
Analytics::track('post.created', [
    'user_id' => $user->id,
    'post_id' => $post->id,
    'timestamp' => now(),
]);
```

### Aggregation

```php
// Daily aggregation job
class AggregateAnalytics
{
    public function handle()
    {
        DB::table('analytics_daily')->insert([
            'date' => today(),
            'total_posts' => Post::whereDate('created_at', today())->count(),
            'total_users' => User::whereDate('created_at', today())->count(),
            'engagement_rate' => $this->calculateEngagement(),
        ]);
    }
}
```

## 🔧 Performance Optimization

### Database Optimization

**1. Query Optimization**
```php
// Eager loading
Post::with(['user', 'hashtags', 'media'])->get();

// Select specific columns
Post::select('id', 'content', 'user_id')->get();

// Chunk processing
Post::chunk(1000, function($posts) {
    // Process posts
});
```

**2. Database Connection Pooling**
```php
'mysql' => [
    'pool' => [
        'min_connections' => 1,
        'max_connections' => 10,
    ],
],
```

### Application Optimization

**1. OPcache**
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
```

**2. Response Compression**
```nginx
gzip on;
gzip_types text/plain text/css application/json application/javascript;
```

**3. Asset Optimization**
```bash
npm run build  # Minify JS/CSS
php artisan optimize  # Cache routes, config, views
```

## 🔄 Scalability Strategy

### Horizontal Scaling

```yaml
# Docker Compose scaling
docker-compose up --scale app=3 --scale queue=2
```

### Load Balancing

```nginx
upstream backend {
    least_conn;
    server app1:9000;
    server app2:9000;
    server app3:9000;
}
```

### Database Replication

```
Master (Write)
    ↓
┌───┴───┬───────┐
Slave1  Slave2  Slave3
(Read)  (Read)  (Read)
```

### Cache Clustering

```php
'redis' => [
    'cluster' => true,
    'clusters' => [
        'default' => [
            ['host' => '127.0.0.1', 'port' => 6379],
            ['host' => '127.0.0.1', 'port' => 6380],
        ],
    ],
],
```

## 📈 Monitoring & Observability

### Metrics Collection

```php
// Performance metrics
PerformanceMonitor::track('api.response_time', $duration);
PerformanceMonitor::track('db.query_count', $queries);
PerformanceMonitor::track('cache.hit_rate', $hitRate);
```

### Health Checks

```php
// /api/health endpoint
return [
    'status' => 'ok',
    'database' => $this->checkDatabase(),
    'cache' => $this->checkCache(),
    'queue' => $this->checkQueue(),
    'disk' => $this->checkDisk(),
];
```

### Error Tracking

```php
// Sentry integration
if (app()->bound('sentry')) {
    app('sentry')->captureException($exception);
}
```

## 🚦 Rate Limiting Strategy

### Implementation

```php
// Per-user rate limiting
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

// Per-endpoint rate limiting
Route::middleware('throttle:posts.create')->post('/posts', ...);
```

### Configuration

```php
'rate_limiting' => [
    'login' => ['max_attempts' => 5, 'window_minutes' => 15],
    'post_create' => ['max_attempts' => 300, 'window_minutes' => 180],
    'search' => ['max_attempts' => 180, 'window_minutes' => 15],
],
```

## 🔄 Deployment Architecture

### CI/CD Pipeline

```yaml
# GitHub Actions
1. Run Tests
2. Build Docker Image
3. Push to Registry
4. Deploy to Production
5. Run Migrations
6. Clear Caches
7. Health Check
```

### Zero-Downtime Deployment

```bash
# Blue-Green deployment
1. Deploy to green environment
2. Run health checks
3. Switch traffic to green
4. Keep blue as backup
```

## 📦 Microservices (Future)

### Planned Services

```
┌─────────────┐  ┌─────────────┐  ┌─────────────┐
│   Auth      │  │   Posts     │  │  Messages   │
│  Service    │  │  Service    │  │  Service    │
└─────────────┘  └─────────────┘  └─────────────┘
       ↓                ↓                ↓
┌──────────────────────────────────────────────┐
│           API Gateway / Service Mesh          │
└──────────────────────────────────────────────┘
```

## 🎯 Best Practices

### Code Organization

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   ├── Middleware/
│   └── Requests/
├── Services/
│   ├── PostService.php
│   ├── UserService.php
│   └── CacheService.php
├── Repositories/
│   ├── PostRepository.php
│   └── UserRepository.php
├── Models/
├── Events/
├── Listeners/
└── Jobs/
```

### Dependency Injection

```php
class PostController
{
    public function __construct(
        private PostService $postService,
        private CacheService $cacheService
    ) {}
}
```

### Error Handling

```php
try {
    $post = $this->postService->create($data);
} catch (ValidationException $e) {
    return response()->json(['error' => $e->errors()], 422);
} catch (\Exception $e) {
    Log::error('Post creation failed', ['error' => $e->getMessage()]);
    return response()->json(['error' => 'Server error'], 500);
}
```

---

## 📚 References

- [Laravel Documentation](https://laravel.com/docs)
- [Redis Documentation](https://redis.io/documentation)
- [Meilisearch Documentation](https://docs.meilisearch.com)
- [Docker Documentation](https://docs.docker.com)

---

Built with modern architecture principles for scalability, maintainability, and performance.
