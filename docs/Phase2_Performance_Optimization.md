# فاز 2: بهینهسازی عملکرد - راهنمای اجرا

## 📋 **اطلاعات کلی فاز**

- **مدت زمان**: 3 ماه (12 هفته)
- **بودجه**: $240,000
- **اولویت**: High
- **هدف**: حل مشکلات عملکردی و بهینهسازی

---

## 👥 **تیم مورد نیاز**

| نقش | تعداد | مسئولیت اصلی |
|-----|-------|---------------|
| Senior Backend Developer | 3 | Query Optimization، API Performance |
| Database Administrator | 1 | Database Architecture، Indexing |
| DevOps Engineer | 1 | Infrastructure، Monitoring |
| Performance Engineer | 1 | Load Testing، Optimization |

### هزینه تیم:
- Backend Developers: $12K/month × 3 = $36K/month
- Database Administrator: $15K/month × 1 = $15K/month
- DevOps Engineer: $10K/month × 1 = $10K/month
- Performance Engineer: $12K/month × 1 = $12K/month
- **مجموع ماهانه**: $73K × 3 ماه = $219K

---

## 🎯 **اهداف فاز**

### اهداف اصلی:
1. **حذف N+1 Query Problems**
2. **پیادهسازی Multi-layer Caching**
3. **بهینهسازی Database Architecture**
4. **بهبود API Response Time**
5. **پیادهسازی Media Processing Pipeline**

### معیارهای موفقیت:
- ✅ Response time < 100ms (95th percentile)
- ✅ Database queries optimized (< 50ms average)
- ✅ 10K concurrent users supported
- ✅ 99.95% uptime achieved
- ✅ Memory usage < 512MB per instance

---

## 📅 **برنامه زمانی تفصیلی**

### ماه 1: Database Optimization

#### هفته 1-2: Query Analysis و Optimization
```yaml
Week 1:
  Days 1-3: N+1 Query Identification
    - Timeline query analysis
    - User profile query review
    - Post loading optimization
    - Relationship loading audit
  
  Days 4-5: Eager Loading Implementation
    - Timeline eager loading
    - User posts optimization
    - Comment loading improvement
    - Media attachment optimization

Week 2:
  Days 1-3: Database Indexing Strategy
    - Query performance analysis
    - Index creation plan
    - Composite index design
    - Foreign key optimization
  
  Days 4-5: Index Implementation
    - Timeline indexes
    - Search indexes
    - User relationship indexes
    - Performance validation
```

#### هفته 3-4: Database Architecture
```yaml
Week 3:
  Days 1-3: Read/Write Splitting Setup
    - Master-slave configuration
    - Read replica setup
    - Connection routing logic
    - Failover mechanism
  
  Days 4-5: Connection Pooling
    - Connection pool configuration
    - Pool size optimization
    - Connection lifecycle management
    - Monitoring setup

Week 4:
  Days 1-3: Query Caching Layer
    - Redis query cache setup
    - Cache key strategy
    - Cache invalidation logic
    - Performance testing
  
  Days 4-5: Database Sharding Preparation
    - Sharding strategy design
    - Shard key selection
    - Migration planning
    - Testing framework
```

### ماه 2: API Performance و Caching

#### هفته 5-6: API Response Optimization
```yaml
Week 5:
  Days 1-3: JSON Serialization Improvement
    - Response structure optimization
    - Unnecessary data elimination
    - Nested relationship optimization
    - Compression implementation
  
  Days 4-5: Pagination Optimization
    - Cursor-based pagination
    - Efficient counting queries
    - Lazy loading implementation
    - Performance benchmarking

Week 6:
  Days 1-3: API Versioning Strategy
    - Version management system
    - Backward compatibility
    - Deprecation strategy
    - Documentation update
  
  Days 4-5: Response Compression
    - Gzip compression setup
    - Brotli compression
    - Content negotiation
    - Performance measurement
```

#### هفته 7-8: Background Processing
```yaml
Week 7:
  Days 1-3: Queue System Enhancement
    - Redis queue optimization
    - Job prioritization system
    - Batch processing implementation
    - Dead letter queue setup
  
  Days 4-5: Job Monitoring
    - Queue monitoring dashboard
    - Failed job handling
    - Performance metrics
    - Alert system

Week 8:
  Days 1-3: Async Processing
    - Event-driven architecture
    - Async notification system
    - Background task optimization
    - Resource management
  
  Days 4-5: Caching Strategy Implementation
    - Multi-layer cache design
    - Cache warming strategy
    - Cache invalidation patterns
    - Performance validation
```

### ماه 3: Media Processing و Load Testing

#### هفته 9-10: Media Optimization
```yaml
Week 9:
  Days 1-3: Image Processing Pipeline
    - Image compression system
    - Multiple format generation
    - Thumbnail creation
    - CDN integration
  
  Days 4-5: Video Processing System
    - Video transcoding pipeline
    - Multiple quality generation
    - Streaming optimization
    - Storage management

Week 10:
  Days 1-3: CDN Integration
    - CloudFront setup
    - Cache policies configuration
    - Origin optimization
    - Performance testing
  
  Days 4-5: Storage Optimization
    - S3 storage classes
    - Lifecycle policies
    - Cost optimization
    - Backup strategy
```

#### هفته 11-12: Performance Testing و Monitoring
```yaml
Week 11:
  Days 1-3: Load Testing Implementation
    - Test scenario development
    - Load testing tools setup
    - Stress testing execution
    - Bottleneck identification
  
  Days 4-5: Performance Optimization
    - Critical path optimization
    - Resource usage optimization
    - Memory leak fixes
    - CPU usage optimization

Week 12:
  Days 1-3: Monitoring Setup
    - APM tool integration
    - Custom metrics creation
    - Alert configuration
    - Dashboard development
  
  Days 4-5: Final Validation
    - End-to-end testing
    - Performance benchmarking
    - Documentation completion
    - Knowledge transfer
```

---

## 🛠️ **تسکهای فنی تفصیلی**

### 1. N+1 Query Elimination

#### Timeline Optimization:
```php
// Before (N+1 Problem)
class TimelineController
{
    public function index()
    {
        $posts = Post::latest()->take(20)->get();
        
        foreach ($posts as $post) {
            $post->user; // N+1 query
            $post->likes_count; // N+1 query
            $post->comments_count; // N+1 query
        }
        
        return $posts;
    }
}

// After (Optimized)
class OptimizedTimelineController
{
    public function index()
    {
        $posts = Post::with([
                'user:id,name,username,avatar',
                'hashtags:id,name,slug'
            ])
            ->withCount(['likes', 'comments', 'quotes'])
            ->select([
                'id', 'user_id', 'content', 'created_at',
                'image', 'gif_url', 'quoted_post_id'
            ])
            ->latest()
            ->take(20)
            ->get();
        
        return PostResource::collection($posts);
    }
}
```

#### Repository Pattern Enhancement:
```php
// app/Repositories/OptimizedPostRepository.php
class OptimizedPostRepository implements PostRepositoryInterface
{
    public function getTimelinePosts(int $userId, int $limit = 20): Collection
    {
        $followingIds = $this->getCachedFollowingIds($userId);
        
        return Post::whereIn('user_id', $followingIds)
            ->with($this->getOptimizedRelations())
            ->withCount($this->getCountRelations())
            ->select($this->getRequiredColumns())
            ->published()
            ->latest('published_at')
            ->limit($limit)
            ->get();
    }
    
    private function getOptimizedRelations(): array
    {
        return [
            'user:id,name,username,avatar',
            'hashtags:id,name,slug',
            'quotedPost:id,content,user_id',
            'quotedPost.user:id,name,username'
        ];
    }
    
    private function getCachedFollowingIds(int $userId): array
    {
        return Cache::remember(
            "following:{$userId}",
            600,
            fn() => Follow::where('follower_id', $userId)
                ->pluck('following_id')
                ->push($userId)
                ->toArray()
        );
    }
}
```

### 2. Multi-layer Caching Implementation

#### Cache Service:
```php
// app/Services/CacheService.php
class CacheService
{
    private const CACHE_TAGS = [
        'timeline' => 300,    // 5 minutes
        'user' => 600,       // 10 minutes
        'post' => 1800,      // 30 minutes
        'trending' => 900    // 15 minutes
    ];
    
    public function getTimeline(int $userId, int $limit = 20): array
    {
        $cacheKey = "timeline:{$userId}:{$limit}";
        
        return Cache::tags(['timeline', "user:{$userId}"])
            ->remember($cacheKey, self::CACHE_TAGS['timeline'], function () use ($userId, $limit) {
                return $this->generateTimeline($userId, $limit);
            });
    }
    
    public function invalidateUserCache(int $userId): void
    {
        Cache::tags(["user:{$userId}"])->flush();
    }
    
    public function warmupCache(): array
    {
        $results = [];
        
        // Warm trending posts
        $results['trending'] = $this->getTrendingPosts();
        
        // Warm popular users
        $popularUsers = User::withCount('followers')
            ->orderBy('followers_count', 'desc')
            ->limit(100)
            ->get();
            
        foreach ($popularUsers as $user) {
            $results["user_{$user->id}"] = $this->getUserProfile($user->id);
        }
        
        return $results;
    }
}
```

#### Redis Cluster Configuration:
```php
// config/database.php - Redis Cluster
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    
    'options' => [
        'cluster' => env('REDIS_CLUSTER_ENABLED', true) ? 'redis' : false,
        'prefix' => env('REDIS_PREFIX', 'wonderway:'),
    ],
    
    'clusters' => [
        'default' => [
            [
                'host' => env('REDIS_CLUSTER_NODE_1_HOST', '127.0.0.1'),
                'port' => env('REDIS_CLUSTER_NODE_1_PORT', 7000),
                'password' => env('REDIS_PASSWORD'),
            ],
            [
                'host' => env('REDIS_CLUSTER_NODE_2_HOST', '127.0.0.1'),
                'port' => env('REDIS_CLUSTER_NODE_2_PORT', 7001),
                'password' => env('REDIS_PASSWORD'),
            ],
            [
                'host' => env('REDIS_CLUSTER_NODE_3_HOST', '127.0.0.1'),
                'port' => env('REDIS_CLUSTER_NODE_3_PORT', 7002),
                'password' => env('REDIS_PASSWORD'),
            ],
        ],
    ],
];
```

### 3. Database Optimization

#### Index Strategy:
```sql
-- Timeline Performance Indexes
CREATE INDEX idx_posts_timeline ON posts (user_id, published_at DESC, is_draft);
CREATE INDEX idx_posts_trending ON posts (created_at, likes_count, comments_count);

-- User Relationship Indexes
CREATE INDEX idx_follows_follower ON follows (follower_id, created_at);
CREATE INDEX idx_follows_following ON follows (following_id, created_at);

-- Search Indexes
CREATE INDEX idx_posts_content_search ON posts USING gin(to_tsvector('english', content));
CREATE INDEX idx_users_search ON users USING gin(to_tsvector('english', name || ' ' || username));

-- Hashtag Indexes
CREATE INDEX idx_hashtag_post_hashtag ON hashtag_post (hashtag_id, created_at);
CREATE INDEX idx_hashtag_post_post ON hashtag_post (post_id);

-- Notification Indexes
CREATE INDEX idx_notifications_user_unread ON notifications (user_id, read_at, created_at);
```

#### Database Connection Optimization:
```php
// config/database.php - Connection Optimization
'mysql' => [
    'read' => [
        'host' => [
            env('DB_READ_HOST_1', '127.0.0.1'),
            env('DB_READ_HOST_2', '127.0.0.1'),
        ],
    ],
    'write' => [
        'host' => [env('DB_WRITE_HOST', '127.0.0.1')],
    ],
    'sticky' => true,
    
    // Connection Pool Settings
    'options' => [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_TIMEOUT => 30,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET sql_mode='STRICT_TRANS_TABLES'",
    ],
    
    // Performance Settings
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'engine' => 'InnoDB',
    'strict' => true,
];
```

### 4. Media Processing Pipeline

#### Image Processing Service:
```php
// app/Services/ImageProcessingService.php
class ImageProcessingService
{
    private $sizes = [
        'thumbnail' => [150, 150],
        'small' => [300, 300],
        'medium' => [600, 600],
        'large' => [1200, 1200]
    ];
    
    public function processImage(UploadedFile $file): array
    {
        $results = [];
        $originalPath = $this->storeOriginal($file);
        
        foreach ($this->sizes as $size => $dimensions) {
            $results[$size] = $this->createResizedImage(
                $originalPath,
                $dimensions[0],
                $dimensions[1],
                $size
            );
        }
        
        // Generate WebP versions
        foreach ($results as $size => $path) {
            $results["{$size}_webp"] = $this->convertToWebP($path);
        }
        
        return $results;
    }
    
    private function createResizedImage(string $path, int $width, int $height, string $size): string
    {
        $image = Image::make(Storage::path($path));
        
        $image->fit($width, $height, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        
        $resizedPath = "images/{$size}/" . basename($path);
        Storage::put($resizedPath, $image->encode('jpg', 85));
        
        return $resizedPath;
    }
}
```

#### Video Processing Queue:
```php
// app/Jobs/ProcessVideoJob.php
class ProcessVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $timeout = 3600; // 1 hour
    public $tries = 3;
    
    public function handle(VideoProcessingService $service)
    {
        $qualities = ['480p', '720p', '1080p'];
        
        foreach ($qualities as $quality) {
            $service->transcode($this->video, $quality);
        }
        
        // Generate thumbnail
        $service->generateThumbnail($this->video);
        
        // Update video status
        $this->video->update(['status' => 'processed']);
        
        // Notify user
        $this->video->user->notify(new VideoProcessedNotification($this->video));
    }
}
```

---

## 📊 **ابزارها و تکنولوژیها**

### Performance Tools:
```yaml
Monitoring:
  - New Relic APM
  - DataDog
  - Prometheus + Grafana
  - Laravel Telescope

Load Testing:
  - Apache JMeter
  - Artillery.io
  - K6
  - Locust

Database Tools:
  - MySQL Workbench
  - Percona Toolkit
  - pt-query-digest
  - MySQL Tuner
```

### Caching Solutions:
```yaml
Redis:
  - Redis Cluster
  - Redis Sentinel
  - Redis Modules (RedisJSON, RedisSearch)

CDN:
  - CloudFront
  - CloudFlare
  - KeyCDN
  - BunnyCDN
```

---

## 🔍 **تست و اعتبارسنجی**

### Performance Testing Suite:
```php
// tests/Performance/TimelinePerformanceTest.php
class TimelinePerformanceTest extends TestCase
{
    public function test_timeline_response_time()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $startTime = microtime(true);
        
        $response = $this->getJson('/api/timeline');
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        $response->assertStatus(200);
        $this->assertLessThan(100, $responseTime, 'Timeline response time should be under 100ms');
    }
    
    public function test_database_query_count()
    {
        DB::enableQueryLog();
        
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $this->getJson('/api/timeline');
        
        $queries = DB::getQueryLog();
        $this->assertLessThan(5, count($queries), 'Timeline should execute less than 5 queries');
    }
}
```

### Load Testing Script:
```javascript
// load-test.js (K6)
import http from 'k6/http';
import { check, sleep } from 'k6';

export let options = {
  stages: [
    { duration: '2m', target: 100 },   // Ramp up
    { duration: '5m', target: 100 },   // Stay at 100 users
    { duration: '2m', target: 200 },   // Ramp up to 200
    { duration: '5m', target: 200 },   // Stay at 200
    { duration: '2m', target: 0 },     // Ramp down
  ],
  thresholds: {
    http_req_duration: ['p(95)<100'], // 95% of requests under 100ms
    http_req_failed: ['rate<0.1'],    // Error rate under 10%
  },
};

export default function () {
  let response = http.get('http://localhost:8000/api/timeline', {
    headers: { 'Authorization': 'Bearer ' + __ENV.API_TOKEN },
  });
  
  check(response, {
    'status is 200': (r) => r.status === 200,
    'response time < 100ms': (r) => r.timings.duration < 100,
  });
  
  sleep(1);
}
```

---

## 📈 **نظارت و گزارشگیری**

### Performance Dashboard:
```yaml
Key Metrics:
  - Response time (p50, p95, p99)
  - Throughput (requests/second)
  - Error rate
  - Database query time
  - Cache hit ratio
  - Memory usage
  - CPU utilization

Alerts:
  - Response time > 200ms
  - Error rate > 5%
  - Database queries > 100ms
  - Cache hit ratio < 80%
  - Memory usage > 80%
```

### Weekly Performance Report:
```markdown
# Performance Report - Week X

## Summary
- Average response time: 45ms (target: <100ms) ✅
- Peak throughput: 5,000 req/sec
- Error rate: 0.2% (target: <1%) ✅
- Cache hit ratio: 92% (target: >80%) ✅

## Database Performance
- Average query time: 25ms
- Slow queries: 12 (improved from 156)
- Index usage: 98%

## Optimizations Completed
- Timeline query optimization (-60% response time)
- Image processing pipeline (+300% throughput)
- Cache warming strategy (+25% hit ratio)

## Next Week Focus
- Video processing optimization
- Mobile API performance
- Search query optimization
```

---

## ✅ **Deliverables**

### Month 3 Deliverables:
1. **Optimized Codebase**
   - N+1 queries eliminated
   - Efficient database queries
   - Optimized API responses
   - Enhanced caching system

2. **Infrastructure Improvements**
   - Database read/write splitting
   - Redis cluster setup
   - CDN integration
   - Media processing pipeline

3. **Performance Testing**
   - Load testing suite
   - Performance benchmarks
   - Monitoring dashboard
   - Alert system

4. **Documentation**
   - Performance optimization guide
   - Database optimization manual
   - Caching strategy document
   - Troubleshooting guide

---

## 🚨 **ریسکها و کاهش آنها**

### High Risk:
```yaml
Risk: Database Migration Issues
Mitigation: Staged rollout, backup strategy, rollback plan

Risk: Cache Invalidation Problems
Mitigation: Conservative TTL, manual invalidation tools

Risk: Performance Regression
Mitigation: Continuous monitoring, automated alerts
```

### Medium Risk:
```yaml
Risk: CDN Configuration Issues
Mitigation: Gradual traffic migration, fallback mechanisms

Risk: Memory Leaks
Mitigation: Memory profiling, automated restarts
```

---

*این سند راهنمای کامل اجرای فاز 2 است و باید بهروزرسانی منظم شود.*