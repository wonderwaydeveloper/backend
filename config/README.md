# Configuration Files Documentation

This directory contains all configuration files for the Clevlance application. All hardcoded values have been moved to these config files for better maintainability.

## 📁 Configuration Files

### 1. cache_ttl.php
**Purpose**: Cache Time-To-Live configurations

**Usage**:
```php
Cache::remember($key, config('cache_ttl.ttl.trending'), function() {
    // ...
});
```

**Keys**:
- `ab_test`, `cpu_usage`, `memory_usage`, `active_connections`
- `queue_size`, `critical_assets`, `conversion_funnel`, `conversion_rate`
- `server_stats`, `localization`, `post`, `user_posts`, `following`
- `timeline`, `popular_content`, `search`, `trending`, `engagement`

---

### 2. performance.php
**Purpose**: Performance monitoring and delay configurations

**Usage**:
```php
sleep(config('performance.monitoring.simulation_delay_seconds'));
```

**Keys**:
- `monitoring.simulation_delay_seconds`: 0.05
- `email.rate_limit_delay_seconds`: 5

---

### 3. limits.php
**Purpose**: Role-based limits, rate limits, and trending thresholds

**Usage**:
```php
// Trending thresholds
->having('recent_posts_count', '>=', config('limits.trending.thresholds.hashtag_min_posts'))

// Rate limits
$limit = config('limits.rate_limits.auth.login');
```

**Keys**:
- `trending.thresholds.hashtag_min_posts`: 5
- `trending.thresholds.post_min_engagement`: 10
- `trending.thresholds.user_min_followers`: 100
- `roles`: user, verified, premium, organization limits
- `rate_limits`: auth, social, search, trending, messaging, polls, realtime

---

### 4. services.php
**Purpose**: External service credentials and analytics event types

**Usage**:
```php
// Analytics events
->whereIn('event_type', config('services.analytics.event_types.engagement'))

// External services
$apiKey = config('services.twilio.api_key');
```

**Keys**:
- `analytics.event_types.engagement`: ['post_like', 'post_comment', 'post_repost']
- `analytics.event_types.post_engagement`: ['post_like', 'post_comment', 'post_repost', 'post_share', 'link_click']
- `analytics.event_types.active_user`: ['login', 'post_create', 'comment', 'like']
- `twilio`, `firebase`, `sendgrid`, `google`, `recaptcha`: External service configs

---

### 5. status.php
**Purpose**: Status string values for models

**Usage**:
```php
->where('status', config('status.ab_test.active'))
```

**Keys**:
- `ab_test`: active, paused, completed
- `community_join_request`: pending, approved, rejected
- `community_note`: pending, approved, rejected
- `report`: pending, resolved, dismissed
- `scheduled_post`: pending, published, failed
- `space`: live, ended, scheduled
- `space_participant`: invited, joined, left
- `subscription`: active, cancelled, expired

---

### 6. throttle.php
**Purpose**: Rate limiting configurations

**Usage**:
```php
->middleware('throttle:' . config('throttle.auth.login'))
```

**Keys**:
- `auth`: login (5,1), register (3,1), password_reset (10,1), email_verify (10,1)
- `social`: follow (400,1440), block (10,1), mute (20,1)
- `search`: posts (450,15), users (180,15), hashtags (180,15), all (180,15)
- `messaging`: send (60,1)
- `hashtags`: trending (75,15), search (180,15), show (900,15)
- `trending`: hashtags (75,15), posts (75,15), users (75,15), refresh (15,15)
- `polls`: create (10,1), vote (20,1), results (60,1)
- `moderation`: report (5,1)
- `mentions`: search (60,1), view (60,1)
- `realtime`: default (60,1)

---

### 7. validation.php
**Purpose**: Validation rules max/min values

**Usage**:
```php
'title' => 'required|string|max:' . config('validation.max.title')
```

**Keys**:
- `max`: name (100), title (100), description (500), content (300), url (255)
- `max`: token (500), reason (200), text_short (50), text_medium (100), text_long (200)
- `max`: array_small (4), array_medium (10), array_large (25)
- `max`: age (100), percentage (100), instances (10), sources (3), tags (5)
- `min`: search (1), mention (2), community_note (10), poll_options (2)
- `min`: thread_posts (2), moment_posts (2), age (13), instances (1)

---

### 8. queue.php (Extended)
**Purpose**: Queue name mappings

**Usage**:
```php
$queue = config('queue.names.high');
```

**Keys**:
- `names.high`: 'high'
- `names.default`: 'default'
- `names.low`: 'low'
- `names.image_processing`: 'image-processing'

---

### 9. authentication.php (Extended)
**Purpose**: Authentication token and password lengths

**Usage**:
```php
Str::random(config('authentication.device.token_length'))
```

**Keys**:
- `device.token_length`: 40
- `social.password_length`: 32

---

### 10. pagination.php (Extended)
**Purpose**: Pagination limits for all resource types

**Usage**:
```php
->paginate(config('pagination.posts'))
```

**Keys**:
- `default`: 20, `posts`: 20, `messages`: 50, `notifications`: 20
- `users`: 20, `comments`: 20, `bookmarks`: 20, `communities`: 20
- `follows`: 20, `hashtags`: 20, `lists`: 20, `reports`: 20
- `reposts`: 20, `likes`: 20, `trending`: 10, `suggestions`: 10
- `search`: 20, `activities`: 50, `cache_warmup`: 100

---

## 🔧 HTTP Status Codes

All HTTP status codes now use Symfony Response constants:

```php
use Symfony\Component\HttpFoundation\Response;

return response()->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
return response()->json(['error' => 'Bad request'], Response::HTTP_BAD_REQUEST);
return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
return response()->json(['error' => 'Server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
```

---

## 📝 Best Practices

1. **Always use config values** instead of hardcoding
2. **Use descriptive config keys** for better maintainability
3. **Document new config values** when adding them
4. **Keep related configs together** in the same file
5. **Use environment variables** for environment-specific values

---

## 🚫 What NOT to Move to Config

Some values should remain in code as they are algorithmic:

1. **Sort fields**: `orderBy('trend_score', 'desc')` - Dynamic query fields
2. **Algorithm coefficients**: `* 0.1`, `* 0.5` - Mathematical weights
3. **SQL calculations**: Time decay factors, engagement scores

These are part of the application logic and should not be configurable.
