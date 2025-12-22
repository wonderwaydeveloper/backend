# WonderWay Code Refactoring - فازهای اجرایی

## 🎯 هدف: Refactoring عملی توسط AI Assistant

### Phase 1: Controller Cleanup (روز 1-2)

#### 1.1 PostController Refactoring
```bash
# Files to modify:
- app/Http/Controllers/Api/PostController.php
- app/Services/PostService.php (new)
- app/Http/Requests/CreatePostRequest.php (enhance)
```

**اقدامات:**
1. Extract business logic از PostController
2. Create PostService class
3. Implement dependency injection
4. Update validation rules

#### 1.2 UserController & AuthController
```bash
# Files to modify:
- app/Http/Controllers/Api/AuthController.php
- app/Http/Controllers/Api/ProfileController.php
- app/Services/UserService.php (new)
- app/Services/AuthService.php (new)
```

### Phase 2: Service Layer Implementation (روز 3-4)

#### 2.1 Core Services Creation
```bash
# New files to create:
- app/Services/PostService.php
- app/Services/UserService.php
- app/Services/NotificationService.php
- app/Services/MediaService.php
- app/Services/TimelineService.php
```

#### 2.2 Service Provider Binding
```bash
# Files to modify:
- app/Providers/AppServiceProvider.php
- config/app.php (if needed)
```

### Phase 3: Repository Pattern (روز 5-6)

#### 3.1 Repository Interfaces
```bash
# New files to create:
- app/Contracts/PostRepositoryInterface.php
- app/Contracts/UserRepositoryInterface.php
- app/Contracts/NotificationRepositoryInterface.php
```

#### 3.2 Repository Implementations
```bash
# New files to create:
- app/Repositories/PostRepository.php
- app/Repositories/UserRepository.php
- app/Repositories/NotificationRepository.php
```

#### 3.3 Repository Service Provider
```bash
# Files to modify:
- app/Providers/RepositoryServiceProvider.php (new)
- config/app.php
```

### Phase 4: Request Validation Enhancement (روز 7-8)

#### 4.1 Form Request Classes
```bash
# New/Enhanced files:
- app/Http/Requests/CreatePostRequest.php
- app/Http/Requests/UpdatePostRequest.php
- app/Http/Requests/CreateCommentRequest.php
- app/Http/Requests/UpdateProfileRequest.php
- app/Http/Requests/FollowUserRequest.php
```

#### 4.2 Custom Validation Rules
```bash
# New files:
- app/Rules/ValidHashtag.php
- app/Rules/ValidMention.php
- app/Rules/ValidImageSize.php
```

### Phase 5: Exception Handling (روز 9-10)

#### 5.1 Custom Exceptions
```bash
# New files:
- app/Exceptions/PostNotFoundException.php
- app/Exceptions/UserNotFoundException.php
- app/Exceptions/UnauthorizedActionException.php
- app/Exceptions/ValidationException.php
```

#### 5.2 Exception Handler Enhancement
```bash
# Files to modify:
- app/Exceptions/Handler.php
```

### Phase 6: Query Optimization (روز 11-12)

#### 6.1 Eloquent Optimization
```bash
# Files to modify:
- app/Models/Post.php (add scopes, optimize relations)
- app/Models/User.php (optimize queries)
- app/Models/Comment.php (eager loading)
```

#### 6.2 Repository Query Enhancement
```bash
# Enhance existing repositories with:
- Eager loading
- Query scopes
- Caching strategies
```

### Phase 7: Code Standards & Documentation (روز 13-14)

#### 7.1 PSR-12 Compliance
```bash
# Run and fix:
./vendor/bin/php-cs-fixer fix
./vendor/bin/phpstan analyse
```

#### 7.2 Documentation Enhancement
```bash
# Add PHPDoc to:
- All service methods
- Repository methods
- Controller actions
- Model relationships
```

## 🔧 Implementation Steps

### Step 1: PostController Refactoring
```php
// Current: Fat Controller
class PostController {
    public function store(Request $request) {
        // 50+ lines of code
    }
}

// Target: Thin Controller
class PostController {
    public function store(CreatePostRequest $request, PostService $postService) {
        return $postService->createPost($request->validated());
    }
}
```

### Step 2: Service Creation
```php
// app/Services/PostService.php
class PostService {
    public function __construct(
        private PostRepositoryInterface $postRepository,
        private NotificationService $notificationService
    ) {}
    
    public function createPost(array $data): Post {
        // Business logic here
    }
}
```

### Step 3: Repository Implementation
```php
// app/Repositories/PostRepository.php
class PostRepository implements PostRepositoryInterface {
    public function create(array $data): Post {
        return Post::create($data);
    }
    
    public function getTimelinePosts(int $userId): Collection {
        return Post::with(['user', 'likes'])
            ->whereIn('user_id', $this->getFollowingIds($userId))
            ->latest()
            ->paginate(20);
    }
}
```

## 📋 Daily Execution Plan

### Day 1: PostController + PostService
**Files to create/modify:**
1. `app/Services/PostService.php` (new)
2. `app/Http/Controllers/Api/PostController.php` (refactor)
3. `app/Http/Requests/CreatePostRequest.php` (enhance)

**Tasks:**
- Extract `store()` method logic
- Extract `update()` method logic  
- Extract `destroy()` method logic
- Create PostService with business logic
- Update controller to use service

### Day 2: UserController + AuthController
**Files to create/modify:**
1. `app/Services/UserService.php` (new)
2. `app/Services/AuthService.php` (new)
3. `app/Http/Controllers/Api/AuthController.php` (refactor)
4. `app/Http/Controllers/Api/ProfileController.php` (refactor)

### Day 3: Repository Interfaces
**Files to create:**
1. `app/Contracts/PostRepositoryInterface.php`
2. `app/Contracts/UserRepositoryInterface.php`
3. `app/Contracts/NotificationRepositoryInterface.php`

### Day 4: Repository Implementations
**Files to create:**
1. `app/Repositories/PostRepository.php`
2. `app/Repositories/UserRepository.php`
3. `app/Repositories/NotificationRepository.php`
4. `app/Providers/RepositoryServiceProvider.php`

### Day 5: Request Validation
**Files to create/enhance:**
1. `app/Http/Requests/CreatePostRequest.php`
2. `app/Http/Requests/UpdatePostRequest.php`
3. `app/Http/Requests/CreateCommentRequest.php`
4. `app/Http/Requests/UpdateProfileRequest.php`

### Day 6: Exception Handling
**Files to create/modify:**
1. `app/Exceptions/PostNotFoundException.php`
2. `app/Exceptions/UserNotFoundException.php`
3. `app/Exceptions/UnauthorizedActionException.php`
4. `app/Exceptions/Handler.php` (enhance)

### Day 7: Query Optimization
**Files to modify:**
1. `app/Models/Post.php` (add scopes, optimize)
2. `app/Models/User.php` (optimize queries)
3. All repository classes (add eager loading)

## ✅ Success Criteria

### After Each Day:
- [ ] All tests pass: `php artisan test`
- [ ] No breaking changes in API
- [ ] Code follows PSR-12 standards
- [ ] Functionality works as before

### Final Validation:
- [ ] 347 tests passing ✅
- [ ] API responses unchanged
- [ ] Performance maintained/improved
- [ ] Code is cleaner and more maintainable

## 🚀 Ready to Execute

**Confirm to start Phase 1:**
- PostController refactoring
- Extract business logic to PostService
- Enhance validation with CreatePostRequest

**Next command:** "شروع کن با PostController"