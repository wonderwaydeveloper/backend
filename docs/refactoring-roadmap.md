# WonderWay Backend - برنامه Code Refactoring

## 🎯 هدف: بهبود کیفیت کد و Clean Architecture

### Phase 1: Code Cleanup & Structure (1 هفته)

#### 1.1 Controller Refactoring
```php
// Before: Fat Controllers
class PostController {
    public function store(Request $request) {
        // 50+ lines of validation, business logic, database operations
    }
}

// After: Thin Controllers
class PostController {
    public function store(CreatePostRequest $request, PostService $postService) {
        return $postService->createPost($request->validated());
    }
}
```

#### 1.2 Service Layer Enhancement
```php
// app/Services/
├── PostService.php          // Business logic
├── UserService.php          // User operations
├── NotificationService.php  // Notifications
└── MediaService.php         // File handling
```

### Phase 2: Repository Pattern Implementation (1 هفته)

#### 2.1 Repository Interfaces
```php
// app/Contracts/Repositories/
interface PostRepositoryInterface {
    public function create(array $data): Post;
    public function findWithRelations(int $id, array $relations = []): ?Post;
    public function getTimelinePosts(int $userId, int $limit = 20): Collection;
}
```

#### 2.2 Repository Implementation
```php
// app/Repositories/
class PostRepository implements PostRepositoryInterface {
    public function create(array $data): Post {
        return Post::create($data);
    }
    
    public function getTimelinePosts(int $userId, int $limit = 20): Collection {
        return Post::with(['user', 'likes', 'comments'])
            ->whereIn('user_id', $this->getFollowingIds($userId))
            ->latest()
            ->limit($limit)
            ->get();
    }
}
```

#### 2.3 Query Optimization
```php
// Before: N+1 Problem
$posts = Post::all();
foreach($posts as $post) {
    echo $post->user->name; // N+1 queries
}

// After: Eager Loading
$posts = Post::with(['user', 'likes', 'comments.user'])->get();
```

### Phase 3: Design Patterns Implementation (1 هفته)

#### 3.1 Factory Pattern
```php
// app/Factories/
class NotificationFactory {
    public static function create(string $type, array $data): NotificationInterface {
        return match($type) {
            'like' => new LikeNotification($data),
            'comment' => new CommentNotification($data),
            'follow' => new FollowNotification($data),
            default => throw new InvalidArgumentException("Unknown notification type: {$type}")
        };
    }
}
```

#### 3.2 Strategy Pattern
```php
// app/Strategies/
interface CacheStrategy {
    public function get(string $key): mixed;
    public function set(string $key, mixed $value, int $ttl = 3600): bool;
}

class RedisCacheStrategy implements CacheStrategy {
    // Redis implementation
}

class FileCacheStrategy implements CacheStrategy {
    // File cache implementation
}
```

### Phase 4: Validation & Error Handling (1 هفته)

#### 4.1 Form Request Classes
```php
// app/Http/Requests/
class CreatePostRequest extends FormRequest {
    public function rules(): array {
        return [
            'content' => 'required|string|max:280',
            'image' => 'nullable|image|max:2048',
            'reply_settings' => 'in:everyone,following,mentioned'
        ];
    }
    
    public function messages(): array {
        return [
            'content.required' => 'Post content is required',
            'content.max' => 'Post content cannot exceed 280 characters'
        ];
    }
}
```

#### 4.2 Custom Exceptions
```php
// app/Exceptions/
class PostNotFoundException extends Exception {
    public function render($request) {
        return response()->json([
            'error' => 'Post not found',
            'code' => 'POST_NOT_FOUND'
        ], 404);
    }
}
```

### Phase 5: Code Quality & Standards (1 هفته)

#### 5.1 Code Standards
```php
// PSR-12 Compliance
// Type declarations
public function createPost(array $data): Post
{
    return $this->postRepository->create($data);
}

// Proper naming conventions
class PostCreationService // PascalCase for classes
public function getUserTimeline() // camelCase for methods
const MAX_POST_LENGTH = 280; // UPPER_CASE for constants
```

#### 5.2 Documentation & Comments
```php
/**
 * Create a new post for the authenticated user
 * 
 * @param array $data Post data including content, media, etc.
 * @return Post The created post instance
 * @throws ValidationException When post data is invalid
 */
public function createPost(array $data): Post
{
    // Implementation
}
```

### Phase 6: Testing & Performance (1 هفته)

#### 6.1 Unit Tests Enhancement
```php
// tests/Unit/Services/
class PostServiceTest extends TestCase {
    public function test_creates_post_successfully() {
        $mockRepo = Mockery::mock(PostRepositoryInterface::class);
        $mockRepo->shouldReceive('create')->once()->andReturn(new Post());
        
        $service = new PostService($mockRepo);
        $result = $service->createPost(['content' => 'Test']);
        
        $this->assertInstanceOf(Post::class, $result);
    }
}
```

#### 6.2 Performance Optimization
```php
// Database Query Optimization
class PostRepository {
    public function getTimelinePosts(int $userId): Collection {
        return Cache::remember("timeline:{$userId}", 300, function() use ($userId) {
            return Post::with(['user:id,name,username', 'likes:id,post_id'])
                ->select(['id', 'user_id', 'content', 'created_at'])
                ->whereIn('user_id', $this->getFollowingIds($userId))
                ->latest()
                ->limit(20)
                ->get();
        });
    }
}
```

## 📋 Implementation Roadmap

### Week 1: Code Structure & Controllers
```bash
# Day 1-2: Controller Refactoring
- Extract business logic from controllers
- Create thin controllers
- Implement proper HTTP responses

# Day 3-4: Service Layer
- Create service classes
- Move business logic to services
- Implement dependency injection

# Day 5-7: Code Organization
- Organize file structure
- Remove duplicate code
- Apply SOLID principles
```

### Week 2: Repository & Patterns
```bash
# Day 8-10: Repository Pattern
- Create repository interfaces
- Implement repositories
- Bind interfaces to implementations

# Day 11-12: Design Patterns
- Factory pattern for notifications
- Strategy pattern for caching
- Observer pattern for events

# Day 13-14: Code Review & Testing
- Review implemented patterns
- Write unit tests
- Integration testing
```

### Week 3: Validation & Quality
```bash
# Day 15-17: Request Validation
- Create Form Request classes
- Custom validation rules
- Error handling improvement

# Day 18-19: Code Quality
- PSR-12 compliance
- Code documentation
- Static analysis fixes

# Day 20-21: Performance Optimization
- Query optimization
- Caching implementation
- Memory usage optimization
```

## 🔧 Technical Implementation

### 1. Service Layer Pattern
```php
// app/Services/PostService.php
class PostService
{
    public function __construct(
        private PostRepositoryInterface $postRepository,
        private NotificationService $notificationService,
        private CacheService $cacheService
    ) {}
    
    public function createPost(array $data): Post
    {
        $post = $this->postRepository->create($data);
        
        $this->notificationService->notifyFollowers($post);
        $this->cacheService->invalidateUserTimeline($post->user_id);
        
        return $post;
    }
}
```

### 2. Repository Pattern
```php
// app/Repositories/PostRepository.php
class PostRepository implements PostRepositoryInterface
{
    public function create(array $data): Post
    {
        return Post::create($data);
    }
    
    public function findWithUser(int $id): ?Post
    {
        return Post::with('user')->find($id);
    }
    
    public function getTimelinePosts(int $userId, int $limit = 20): Collection
    {
        return Post::with(['user', 'likes'])
            ->whereIn('user_id', $this->getFollowingIds($userId))
            ->latest()
            ->limit($limit)
            ->get();
    }
}
```

### 3. Clean Controller
```php
// app/Http/Controllers/Api/PostController.php
class PostController extends Controller
{
    public function __construct(
        private PostService $postService
    ) {}
    
    public function store(CreatePostRequest $request): JsonResponse
    {
        $post = $this->postService->createPost($request->validated());
        
        return response()->json([
            'data' => new PostResource($post),
            'message' => 'Post created successfully'
        ], 201);
    }
}
```

## 📊 Success Metrics

### Code Quality Targets
- **Cyclomatic Complexity**: < 10 per method
- **Code Duplication**: < 3%
- **Test Coverage**: > 90%
- **PSR-12 Compliance**: 100%

### Performance Targets
- **Response Time**: < 200ms (95th percentile)
- **Memory Usage**: Reduced by 20%
- **Database Queries**: Optimized (no N+1)
- **Cache Hit Rate**: > 80%

## 🚀 Implementation Strategy

### 1. Gradual Refactoring
```bash
# Step-by-step approach
1. Identify code smells
2. Extract methods/classes
3. Apply design patterns
4. Write tests
5. Optimize performance
```

### 2. Code Review Checklist
```php
// Checklist items:
✅ Single Responsibility Principle
✅ Dependency Injection used
✅ Proper error handling
✅ Input validation
✅ Unit tests written
✅ Documentation added
```

### 3. Quality Tools
```bash
# Static Analysis
composer require --dev phpstan/phpstan
composer require --dev squizlabs/php_codesniffer

# Code Formatting
composer require --dev friendsofphp/php-cs-fixer
```

## 📈 Expected Results

### Before Refactoring (Current)
- **Code Quality**: 75/100
- **Maintainability**: 70/100
- **Test Coverage**: 85/100
- **Performance**: 88/100

### After Refactoring (Target)
- **Code Quality**: 95/100
- **Maintainability**: 95/100
- **Test Coverage**: 95/100
- **Performance**: 92/100

## 🎯 Priority Matrix

### High Priority (Must Have)
1. **Controller Refactoring** - Remove fat controllers
2. **Service Layer** - Business logic separation
3. **Repository Pattern** - Data access abstraction
4. **Code Standards** - PSR-12 compliance

### Medium Priority (Should Have)
1. **Design Patterns** - Factory, Strategy patterns
2. **Validation Enhancement** - Form Request classes
3. **Error Handling** - Custom exceptions
4. **Performance Optimization** - Query optimization

### Low Priority (Nice to Have)
1. **Advanced Caching** - Cache strategies
2. **Documentation** - Code documentation
3. **Static Analysis** - Code quality tools

## 📅 Timeline Summary

**Total Duration**: 3 weeks
**Team Size**: 1-2 developers
**Budget Estimate**: Low
**Risk Level**: Low

**Milestones:**
- Week 1: Code structure & controllers ✅
- Week 2: Repository pattern & design patterns ✅
- Week 3: Validation, quality & performance ✅

---
**Expected Result**: Clean, maintainable, high-quality codebase