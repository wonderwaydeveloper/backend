# معماری سیستم WonderWay

## مقدمه

WonderWay بر اساس اصول Clean Architecture و Domain-Driven Design (DDD) طراحی شده است. این معماری امکان مقیاسپذیری، نگهداری آسان و تست پذیری بالا را فراهم میکند.

## نمای کلی معماری

```
┌─────────────────────────────────────────────────────────────┐
│                    Presentation Layer                        │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────┐ │
│  │   Web Routes    │  │  API Controllers │  │  WebSocket   │ │
│  │                 │  │                 │  │   Handlers   │ │
│  └─────────────────┘  └─────────────────┘  └──────────────┘ │
└─────────────────────────────────────────────────────────────┘
                                │
┌─────────────────────────────────────────────────────────────┐
│                   Application Layer                          │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────┐ │
│  │    Services     │  │      DTOs       │  │   Actions    │ │
│  │                 │  │                 │  │              │ │
│  └─────────────────┘  └─────────────────┘  └──────────────┘ │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────┐ │
│  │      CQRS       │  │   Event Bus     │  │  Middleware  │ │
│  │                 │  │                 │  │              │ │
│  └─────────────────┘  └─────────────────┘  └──────────────┘ │
└─────────────────────────────────────────────────────────────┘
                                │
┌─────────────────────────────────────────────────────────────┐
│                     Domain Layer                             │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────┐ │
│  │    Entities     │  │  Value Objects  │  │  Aggregates  │ │
│  │                 │  │                 │  │              │ │
│  └─────────────────┘  └─────────────────┘  └──────────────┘ │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────┐ │
│  │ Domain Services │  │ Repository Intf │  │    Events    │ │
│  │                 │  │                 │  │              │ │
│  └─────────────────┘  └─────────────────┘  └──────────────┘ │
└─────────────────────────────────────────────────────────────┘
                                │
┌─────────────────────────────────────────────────────────────┐
│                 Infrastructure Layer                         │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────┐ │
│  │  Repositories   │  │   External APIs │  │   Database   │ │
│  │                 │  │                 │  │              │ │
│  └─────────────────┘  └─────────────────┘  └──────────────┘ │
│  ┌─────────────────┐  ┌─────────────────┐  ┌──────────────┐ │
│  │     Cache       │  │      Queue      │  │   Storage    │ │
│  │                 │  │                 │  │              │ │
│  └─────────────────┘  └─────────────────┘  └──────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

## لایههای معماری

### 1. Presentation Layer (لایه ارائه)

این لایه مسئول دریافت درخواستها و ارسال پاسخها است.

#### Controllers
```php
app/Http/Controllers/Api/
├── AuthController.php          # احراز هویت
├── PostController.php          # مدیریت پستها
├── UserController.php          # مدیریت کاربران
├── MessageController.php       # پیامرسانی
└── ...
```

#### Middleware
```php
app/Http/Middleware/
├── AuthMiddleware.php          # احراز هویت
├── RateLimitMiddleware.php     # محدودیت نرخ
├── SecurityMiddleware.php      # امنیت
├── CorsMiddleware.php          # CORS
└── ...
```

#### Resources (API Transformers)
```php
app/Http/Resources/
├── UserResource.php            # تبدیل دادههای کاربر
├── PostResource.php            # تبدیل دادههای پست
├── CommentResource.php         # تبدیل دادههای کامنت
└── ...
```

### 2. Application Layer (لایه اپلیکیشن)

این لایه شامل منطق اپلیکیشن و هماهنگی بین لایهها است.

#### Services
```php
app/Services/
├── AuthService.php             # سرویس احراز هویت
├── PostService.php             # سرویس پستها
├── UserService.php             # سرویس کاربران
├── NotificationService.php     # سرویس اعلانات
├── MediaProcessingService.php  # پردازش رسانه
└── ...
```

#### DTOs (Data Transfer Objects)
```php
app/DTOs/
├── UserRegistrationDTO.php     # DTO ثبتنام کاربر
├── CreatePostDTO.php           # DTO ایجاد پست
├── LoginDTO.php                # DTO ورود
└── ...
```

#### Actions
```php
app/Actions/
├── User/
│   ├── CreateUserAction.php    # ایجاد کاربر
│   ├── UpdateUserAction.php    # بروزرسانی کاربر
│   └── DeleteUserAction.php    # حذف کاربر
├── Post/
│   ├── CreatePostAction.php    # ایجاد پست
│   ├── LikePostAction.php      # لایک پست
│   └── SharePostAction.php     # اشتراک پست
└── ...
```

#### CQRS (Command Query Responsibility Segregation)
```php
app/CQRS/
├── Commands/
│   ├── CreatePostCommand.php   # دستور ایجاد پست
│   ├── UpdateUserCommand.php   # دستور بروزرسانی کاربر
│   └── ...
├── Queries/
│   ├── GetTimelineQuery.php    # کوئری تایم لاین
│   ├── GetUserPostsQuery.php   # کوئری پستهای کاربر
│   └── ...
├── Handlers/
│   ├── CreatePostHandler.php   # هندلر ایجاد پست
│   ├── GetTimelineHandler.php  # هندلر تایم لاین
│   └── ...
└── CommandBus.php              # باس دستورات
```

### 3. Domain Layer (لایه دامنه)

هسته اصلی سیستم که شامل منطق کسبوکار است.

#### Entities
```php
app/Domain/Entities/
├── User.php                    # موجودیت کاربر
├── Post.php                    # موجودیت پست
├── Comment.php                 # موجودیت کامنت
├── Follow.php                  # موجودیت فالو
└── ...
```

#### Value Objects
```php
app/Domain/ValueObjects/
├── Email.php                   # شیء مقدار ایمیل
├── Username.php                # شیء مقدار نام کاربری
├── PostContent.php             # شیء مقدار محتوای پست
├── PhoneNumber.php             # شیء مقدار شماره تلفن
└── ...
```

#### Aggregates
```php
app/Domain/
├── User/
│   ├── UserAggregate.php       # تجمیع کاربر
│   ├── UserRepository.php      # رابط مخزن کاربر
│   └── UserService.php         # سرویس دامنه کاربر
├── Post/
│   ├── PostAggregate.php       # تجمیع پست
│   ├── PostRepository.php      # رابط مخزن پست
│   └── PostService.php         # سرویس دامنه پست
└── ...
```

#### Domain Events
```php
app/Events/
├── UserRegistered.php          # رویداد ثبتنام کاربر
├── PostCreated.php             # رویداد ایجاد پست
├── PostLiked.php               # رویداد لایک پست
├── UserFollowed.php            # رویداد فالو کاربر
└── ...
```

### 4. Infrastructure Layer (لایه زیرساخت)

این لایه مسئول پیادهسازی جزئیات فنی است.

#### Repositories
```php
app/Repositories/Eloquent/
├── EloquentUserRepository.php  # پیادهسازی مخزن کاربر
├── EloquentPostRepository.php  # پیادهسازی مخزن پست
├── EloquentCommentRepository.php # پیادهسازی مخزن کامنت
└── ...

app/Repositories/Cache/
├── CachedUserRepository.php    # مخزن کاربر با کش
├── CachedPostRepository.php    # مخزن پست با کش
└── ...
```

#### External Services
```php
app/Infrastructure/External/
├── TwilioSmsService.php        # سرویس SMS
├── FirebasePushService.php     # سرویس Push Notification
├── S3StorageService.php        # سرویس ذخیرهسازی
├── ElasticsearchService.php    # سرویس جستجو
└── ...
```

#### Persistence
```php
app/Infrastructure/Persistence/
├── DatabaseConnection.php      # اتصال پایگاه داده
├── RedisConnection.php         # اتصال Redis
├── ElasticsearchConnection.php # اتصال Elasticsearch
└── ...
```

## الگوهای طراحی استفاده شده

### 1. Repository Pattern

```php
interface UserRepositoryInterface
{
    public function findById(int $id): ?User;
    public function findByEmail(string $email): ?User;
    public function create(array $data): User;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
}

class EloquentUserRepository implements UserRepositoryInterface
{
    public function findById(int $id): ?User
    {
        return User::find($id);
    }
    
    // سایر متدها...
}
```

### 2. Factory Pattern

```php
class PostFactory
{
    public static function createTextPost(string $content, int $userId): Post
    {
        return new Post([
            'content' => $content,
            'user_id' => $userId,
            'type' => 'text'
        ]);
    }
    
    public static function createImagePost(string $content, string $image, int $userId): Post
    {
        return new Post([
            'content' => $content,
            'image' => $image,
            'user_id' => $userId,
            'type' => 'image'
        ]);
    }
}
```

### 3. Observer Pattern

```php
class PostObserver
{
    public function created(Post $post)
    {
        // ارسال اعلان به فالوورها
        event(new PostCreated($post));
    }
    
    public function updated(Post $post)
    {
        // بروزرسانی کش
        Cache::forget("post:{$post->id}");
    }
}
```

### 4. Strategy Pattern

```php
interface NotificationStrategy
{
    public function send(User $user, string $message): bool;
}

class EmailNotificationStrategy implements NotificationStrategy
{
    public function send(User $user, string $message): bool
    {
        // ارسال ایمیل
    }
}

class PushNotificationStrategy implements NotificationStrategy
{
    public function send(User $user, string $message): bool
    {
        // ارسال Push Notification
    }
}

class NotificationService
{
    private NotificationStrategy $strategy;
    
    public function setStrategy(NotificationStrategy $strategy)
    {
        $this->strategy = $strategy;
    }
    
    public function notify(User $user, string $message)
    {
        return $this->strategy->send($user, $message);
    }
}
```

## Event Sourcing

### Event Store

```php
class EventStore
{
    public function append(string $streamId, array $events): void
    {
        foreach ($events as $event) {
            DB::table('event_store')->insert([
                'stream_id' => $streamId,
                'event_type' => get_class($event),
                'event_data' => json_encode($event->toArray()),
                'version' => $this->getNextVersion($streamId),
                'created_at' => now()
            ]);
        }
    }
    
    public function getEvents(string $streamId): Collection
    {
        return DB::table('event_store')
            ->where('stream_id', $streamId)
            ->orderBy('version')
            ->get();
    }
}
```

### Event Handlers

```php
class PostCreatedHandler
{
    public function handle(PostCreated $event): void
    {
        // بروزرسانی تایم لاین فالوورها
        $this->updateFollowersTimeline($event->post);
        
        // ارسال اعلان
        $this->sendNotificationToFollowers($event->post);
        
        // بروزرسانی آمار
        $this->updateUserStats($event->post->user_id);
    }
}
```

## Microservices Architecture

### Service Boundaries

```
┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐
│  User Service   │  │  Post Service   │  │ Message Service │
│                 │  │                 │  │                 │
│ - Authentication│  │ - Create Post   │  │ - Send Message  │
│ - User Profile  │  │ - Like/Comment  │  │ - Conversations │
│ - Follow System │  │ - Timeline      │  │ - Real-time     │
└─────────────────┘  └─────────────────┘  └─────────────────┘
         │                     │                     │
         └─────────────────────┼─────────────────────┘
                               │
┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐
│ Media Service   │  │ Search Service  │  │ Notification    │
│                 │  │                 │  │    Service      │
│ - File Upload   │  │ - Elasticsearch │  │ - Push Notif    │
│ - Image Process │  │ - Indexing      │  │ - Email/SMS     │
│ - Video Stream  │  │ - Search API    │  │ - Real-time     │
└─────────────────┘  └─────────────────┘  └─────────────────┘
```

### Service Communication

#### Synchronous (HTTP/REST)
```php
class UserService
{
    private HttpClient $httpClient;
    
    public function getPostsByUser(int $userId): array
    {
        $response = $this->httpClient->get("/posts-service/users/{$userId}/posts");
        return $response->json();
    }
}
```

#### Asynchronous (Message Queue)
```php
class PostCreatedEvent
{
    public function handle(): void
    {
        // ارسال پیام به صف
        Queue::push(new UpdateTimelineJob($this->post));
        Queue::push(new SendNotificationJob($this->post));
    }
}
```

## Database Design

### شماتیک پایگاه داده

```sql
-- جدول کاربران
CREATE TABLE users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    bio TEXT,
    avatar VARCHAR(255),
    is_private BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_created_at (created_at)
);

-- جدول پستها
CREATE TABLE posts (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    content TEXT NOT NULL,
    image VARCHAR(255),
    video VARCHAR(255),
    likes_count INT DEFAULT 0,
    comments_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_created (user_id, created_at),
    INDEX idx_created_at (created_at),
    FULLTEXT idx_content (content)
);

-- جدول فالوها
CREATE TABLE follows (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    follower_id BIGINT NOT NULL,
    following_id BIGINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_follow (follower_id, following_id),
    INDEX idx_follower (follower_id),
    INDEX idx_following (following_id)
);
```

### Database Sharding

```php
class ShardManager
{
    private array $shards = [
        'shard1' => 'mysql://shard1.wonderway.com',
        'shard2' => 'mysql://shard2.wonderway.com',
        'shard3' => 'mysql://shard3.wonderway.com',
    ];
    
    public function getShardForUser(int $userId): string
    {
        $shardIndex = $userId % count($this->shards);
        return array_keys($this->shards)[$shardIndex];
    }
    
    public function getConnection(string $shard): Connection
    {
        return DB::connection($shard);
    }
}
```

## Caching Strategy

### Multi-Level Caching

```php
class CacheManager
{
    // L1: Application Cache (Redis)
    public function getFromL1(string $key): mixed
    {
        return Redis::get($key);
    }
    
    // L2: Database Query Cache
    public function getFromL2(string $query): mixed
    {
        return DB::table('query_cache')
            ->where('query_hash', md5($query))
            ->first();
    }
    
    // L3: CDN Cache
    public function getFromCDN(string $url): mixed
    {
        return Http::get($url);
    }
}
```

### Cache Invalidation

```php
class CacheInvalidationService
{
    public function invalidateUserCache(int $userId): void
    {
        $keys = [
            "user:{$userId}",
            "user:{$userId}:posts",
            "user:{$userId}:followers",
            "user:{$userId}:following",
            "timeline:{$userId}"
        ];
        
        Redis::del($keys);
    }
    
    public function invalidatePostCache(int $postId): void
    {
        $post = Post::find($postId);
        
        Redis::del([
            "post:{$postId}",
            "user:{$post->user_id}:posts",
            "timeline:*" // Invalidate all timelines
        ]);
    }
}
```

## Security Architecture

### Authentication Flow

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│   Client    │    │     API     │    │   Auth      │
│             │    │  Gateway    │    │  Service    │
└─────────────┘    └─────────────┘    └─────────────┘
        │                  │                  │
        │ 1. Login Request │                  │
        ├─────────────────→│                  │
        │                  │ 2. Validate     │
        │                  ├─────────────────→│
        │                  │                  │
        │                  │ 3. JWT Token    │
        │                  │←─────────────────┤
        │ 4. JWT Token     │                  │
        │←─────────────────┤                  │
        │                  │                  │
        │ 5. API Request   │                  │
        │ + JWT Token      │                  │
        ├─────────────────→│                  │
        │                  │ 6. Verify Token │
        │                  ├─────────────────→│
        │                  │                  │
        │                  │ 7. User Info    │
        │                  │←─────────────────┤
        │ 8. API Response  │                  │
        │←─────────────────┤                  │
```

### Security Layers

```php
class SecurityMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // 1. Rate Limiting
        $this->checkRateLimit($request);
        
        // 2. WAF Rules
        $this->applyWafRules($request);
        
        // 3. Input Validation
        $this->validateInput($request);
        
        // 4. Authentication
        $this->authenticateUser($request);
        
        // 5. Authorization
        $this->authorizeAction($request);
        
        return $next($request);
    }
}
```

## Performance Optimization

### Query Optimization

```php
class OptimizedPostRepository
{
    public function getTimeline(int $userId, int $page = 1): Collection
    {
        return DB::table('posts')
            ->select([
                'posts.*',
                'users.name',
                'users.username',
                'users.avatar'
            ])
            ->join('users', 'posts.user_id', '=', 'users.id')
            ->join('follows', 'posts.user_id', '=', 'follows.following_id')
            ->where('follows.follower_id', $userId)
            ->orderBy('posts.created_at', 'desc')
            ->offset(($page - 1) * 20)
            ->limit(20)
            ->get();
    }
}
```

### Connection Pooling

```php
class DatabaseConnectionPool
{
    private array $connections = [];
    private int $maxConnections = 100;
    
    public function getConnection(): Connection
    {
        if (count($this->connections) < $this->maxConnections) {
            $connection = new PDO($this->dsn, $this->username, $this->password);
            $this->connections[] = $connection;
            return $connection;
        }
        
        // Return existing connection
        return $this->connections[array_rand($this->connections)];
    }
}
```

## Monitoring و Observability

### Metrics Collection

```php
class MetricsCollector
{
    public function recordApiCall(string $endpoint, float $duration): void
    {
        Prometheus::histogram('api_request_duration_seconds')
            ->labelNames(['endpoint'])
            ->observe($duration, [$endpoint]);
    }
    
    public function recordDatabaseQuery(string $query, float $duration): void
    {
        Prometheus::histogram('database_query_duration_seconds')
            ->labelNames(['query_type'])
            ->observe($duration, [$this->getQueryType($query)]);
    }
}
```

### Health Checks

```php
class HealthCheckService
{
    public function checkDatabase(): bool
    {
        try {
            DB::select('SELECT 1');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function checkRedis(): bool
    {
        try {
            Redis::ping();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function getSystemHealth(): array
    {
        return [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'queue' => $this->checkQueue(),
            'storage' => $this->checkStorage(),
        ];
    }
}
```

## Deployment Architecture

### Container Orchestration

```yaml
# docker-compose.production.yml
version: '3.8'

services:
  app:
    image: wonderway/api:latest
    replicas: 3
    environment:
      - APP_ENV=production
      - DB_HOST=mysql-cluster
      - REDIS_HOST=redis-cluster
    
  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    depends_on:
      - app
    
  mysql:
    image: mysql:8.0
    environment:
      - MYSQL_ROOT_PASSWORD=${DB_ROOT_PASSWORD}
    volumes:
      - mysql_data:/var/lib/mysql
    
  redis:
    image: redis:7-alpine
    volumes:
      - redis_data:/data
```

### Load Balancing

```nginx
upstream wonderway_backend {
    server app1:9000 weight=3;
    server app2:9000 weight=3;
    server app3:9000 weight=2;
    server app4:9000 backup;
}

server {
    listen 80;
    server_name api.wonderway.com;
    
    location / {
        proxy_pass http://wonderway_backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }
}
```

## خلاصه

معماری WonderWay بر اساس اصول زیر طراحی شده است:

1. **Separation of Concerns**: جداسازی مسئولیتها در لایههای مختلف
2. **Dependency Inversion**: وابستگی به abstractions نه concrete classes
3. **Single Responsibility**: هر کلاس یک مسئولیت واحد دارد
4. **Open/Closed Principle**: باز برای توسعه، بسته برای تغییر
5. **Interface Segregation**: interfaces کوچک و متمرکز
6. **DRY (Don't Repeat Yourself)**: عدم تکرار کد
7. **SOLID Principles**: پیروی از اصول SOLID

این معماری امکان مقیاسپذیری، نگهداری آسان، تست پذیری بالا و انعطافپذیری در برابر تغییرات را فراهم میکند.