<?php return array (
  'concurrency' => 
  array (
    'default' => 'process',
  ),
  'hashing' => 
  array (
    'driver' => 'bcrypt',
    'bcrypt' => 
    array (
      'rounds' => 12,
      'verify' => true,
      'limit' => NULL,
    ),
    'argon' => 
    array (
      'memory' => 65536,
      'threads' => 1,
      'time' => 4,
      'verify' => true,
    ),
    'rehash_on_login' => true,
  ),
  'view' => 
  array (
    'paths' => 
    array (
      0 => 'C:\\Users\\Venus\\Desktop\\wonderway\\backend\\resources\\views',
    ),
    'compiled' => 'C:\\Users\\Venus\\Desktop\\wonderway\\backend\\storage\\framework\\views',
  ),
  'app' => 
  array (
    'name' => 'Clevlance',
    'env' => 'local',
    'debug' => true,
    'url' => 'http://localhost:8000',
    'frontend_url' => 'http://localhost:3000',
    'asset_url' => NULL,
    'timezone' => 'UTC',
    'locale' => 'en',
    'fallback_locale' => 'en',
    'faker_locale' => 'en_US',
    'cipher' => 'AES-256-CBC',
    'key' => 'base64:GF5nJlHsLKix9KAuxDjvB394uxbtVQP+8SH6cdGCMIw=',
    'previous_keys' => 
    array (
    ),
    'maintenance' => 
    array (
      'driver' => 'file',
      'store' => 'database',
    ),
    'providers' => 
    array (
      0 => 'Illuminate\\Auth\\AuthServiceProvider',
      1 => 'Illuminate\\Broadcasting\\BroadcastServiceProvider',
      2 => 'Illuminate\\Bus\\BusServiceProvider',
      3 => 'Illuminate\\Cache\\CacheServiceProvider',
      4 => 'Illuminate\\Foundation\\Providers\\ConsoleSupportServiceProvider',
      5 => 'Illuminate\\Concurrency\\ConcurrencyServiceProvider',
      6 => 'Illuminate\\Cookie\\CookieServiceProvider',
      7 => 'Illuminate\\Database\\DatabaseServiceProvider',
      8 => 'Illuminate\\Encryption\\EncryptionServiceProvider',
      9 => 'Illuminate\\Filesystem\\FilesystemServiceProvider',
      10 => 'Illuminate\\Foundation\\Providers\\FoundationServiceProvider',
      11 => 'Illuminate\\Hashing\\HashServiceProvider',
      12 => 'Illuminate\\Mail\\MailServiceProvider',
      13 => 'Illuminate\\Notifications\\NotificationServiceProvider',
      14 => 'Illuminate\\Pagination\\PaginationServiceProvider',
      15 => 'Illuminate\\Auth\\Passwords\\PasswordResetServiceProvider',
      16 => 'Illuminate\\Pipeline\\PipelineServiceProvider',
      17 => 'Illuminate\\Queue\\QueueServiceProvider',
      18 => 'Illuminate\\Redis\\RedisServiceProvider',
      19 => 'Illuminate\\Session\\SessionServiceProvider',
      20 => 'Illuminate\\Translation\\TranslationServiceProvider',
      21 => 'Illuminate\\Validation\\ValidationServiceProvider',
      22 => 'Illuminate\\View\\ViewServiceProvider',
      23 => 'App\\Providers\\AppServiceProvider',
      24 => 'App\\Providers\\EventServiceProvider',
      25 => 'App\\Providers\\CleanArchitectureServiceProvider',
      26 => 'App\\Providers\\RepositoryServiceProvider',
    ),
    'aliases' => 
    array (
      'App' => 'Illuminate\\Support\\Facades\\App',
      'Arr' => 'Illuminate\\Support\\Arr',
      'Artisan' => 'Illuminate\\Support\\Facades\\Artisan',
      'Auth' => 'Illuminate\\Support\\Facades\\Auth',
      'Benchmark' => 'Illuminate\\Support\\Benchmark',
      'Blade' => 'Illuminate\\Support\\Facades\\Blade',
      'Broadcast' => 'Illuminate\\Support\\Facades\\Broadcast',
      'Bus' => 'Illuminate\\Support\\Facades\\Bus',
      'Cache' => 'Illuminate\\Support\\Facades\\Cache',
      'Concurrency' => 'Illuminate\\Support\\Facades\\Concurrency',
      'Config' => 'Illuminate\\Support\\Facades\\Config',
      'Context' => 'Illuminate\\Support\\Facades\\Context',
      'Cookie' => 'Illuminate\\Support\\Facades\\Cookie',
      'Crypt' => 'Illuminate\\Support\\Facades\\Crypt',
      'Date' => 'Illuminate\\Support\\Facades\\Date',
      'DB' => 'Illuminate\\Support\\Facades\\DB',
      'Eloquent' => 'Illuminate\\Database\\Eloquent\\Model',
      'Event' => 'Illuminate\\Support\\Facades\\Event',
      'File' => 'Illuminate\\Support\\Facades\\File',
      'Gate' => 'Illuminate\\Support\\Facades\\Gate',
      'Hash' => 'Illuminate\\Support\\Facades\\Hash',
      'Http' => 'Illuminate\\Support\\Facades\\Http',
      'Js' => 'Illuminate\\Support\\Js',
      'Lang' => 'Illuminate\\Support\\Facades\\Lang',
      'Log' => 'Illuminate\\Support\\Facades\\Log',
      'Mail' => 'Illuminate\\Support\\Facades\\Mail',
      'Notification' => 'Illuminate\\Support\\Facades\\Notification',
      'Number' => 'Illuminate\\Support\\Number',
      'Password' => 'Illuminate\\Support\\Facades\\Password',
      'Process' => 'Illuminate\\Support\\Facades\\Process',
      'Queue' => 'Illuminate\\Support\\Facades\\Queue',
      'RateLimiter' => 'Illuminate\\Support\\Facades\\RateLimiter',
      'Redirect' => 'Illuminate\\Support\\Facades\\Redirect',
      'Request' => 'Illuminate\\Support\\Facades\\Request',
      'Response' => 'Illuminate\\Support\\Facades\\Response',
      'Route' => 'Illuminate\\Support\\Facades\\Route',
      'Schedule' => 'Illuminate\\Support\\Facades\\Schedule',
      'Schema' => 'Illuminate\\Support\\Facades\\Schema',
      'Session' => 'Illuminate\\Support\\Facades\\Session',
      'Storage' => 'Illuminate\\Support\\Facades\\Storage',
      'Str' => 'Illuminate\\Support\\Str',
      'Uri' => 'Illuminate\\Support\\Uri',
      'URL' => 'Illuminate\\Support\\Facades\\URL',
      'Validator' => 'Illuminate\\Support\\Facades\\Validator',
      'View' => 'Illuminate\\Support\\Facades\\View',
      'Vite' => 'Illuminate\\Support\\Facades\\Vite',
    ),
  ),
  'auth' => 
  array (
    'defaults' => 
    array (
      'guard' => 'sanctum',
      'passwords' => 'users',
    ),
    'guards' => 
    array (
      'web' => 
      array (
        'driver' => 'session',
        'provider' => 'users',
      ),
      'sanctum' => 
      array (
        'driver' => 'sanctum',
        'provider' => 'users',
      ),
    ),
    'providers' => 
    array (
      'users' => 
      array (
        'driver' => 'eloquent',
        'model' => 'App\\Models\\User',
      ),
    ),
    'passwords' => 
    array (
      'users' => 
      array (
        'provider' => 'users',
        'table' => 'password_reset_tokens',
        'expire' => 15,
        'throttle' => 60,
      ),
    ),
    'password_timeout' => 10800,
  ),
  'broadcasting' => 
  array (
    'default' => 'null',
    'connections' => 
    array (
      'reverb' => 
      array (
        'driver' => 'reverb',
        'key' => '',
        'secret' => '',
        'app_id' => '',
        'options' => 
        array (
          'host' => 'localhost',
          'port' => '8080',
          'scheme' => 'http',
          'useTLS' => false,
        ),
        'client_options' => 
        array (
        ),
      ),
      'pusher' => 
      array (
        'driver' => 'pusher',
        'key' => '',
        'secret' => '',
        'app_id' => '',
        'options' => 
        array (
          'cluster' => 'mt1',
          'host' => 'api-mt1.pusher.com',
          'port' => '443',
          'scheme' => 'https',
          'encrypted' => true,
          'useTLS' => true,
        ),
        'client_options' => 
        array (
        ),
      ),
      'ably' => 
      array (
        'driver' => 'ably',
        'key' => NULL,
      ),
      'log' => 
      array (
        'driver' => 'log',
      ),
      'null' => 
      array (
        'driver' => 'null',
      ),
    ),
  ),
  'cache' => 
  array (
    'default' => 'redis',
    'stores' => 
    array (
      'array' => 
      array (
        'driver' => 'array',
        'serialize' => false,
      ),
      'session' => 
      array (
        'driver' => 'session',
        'key' => '_cache',
      ),
      'database' => 
      array (
        'driver' => 'database',
        'connection' => NULL,
        'table' => 'cache',
      ),
      'file' => 
      array (
        'driver' => 'file',
        'path' => 'C:\\Users\\Venus\\Desktop\\wonderway\\backend\\storage\\framework/cache/data',
      ),
      'memcached' => 
      array (
        'driver' => 'memcached',
        'servers' => 
        array (
          0 => 
          array (
            'host' => '127.0.0.1',
            'port' => 11211,
            'weight' => 100,
          ),
        ),
      ),
      'redis' => 
      array (
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
      ),
      'dynamodb' => 
      array (
        'driver' => 'dynamodb',
        'key' => '',
        'secret' => '',
        'region' => 'us-east-1',
        'table' => 'cache',
        'endpoint' => NULL,
      ),
      'octane' => 
      array (
        'driver' => 'octane',
      ),
      'failover' => 
      array (
        'driver' => 'failover',
        'stores' => 
        array (
          0 => 'database',
          1 => 'array',
        ),
      ),
      'null' => 
      array (
        'driver' => 'null',
      ),
    ),
    'prefix' => 'clevlance_cache_',
    'ttl' => 
    array (
      'user_profile' => 3600,
      'feed' => 1800,
      'search_results' => 300,
      'trending_hashtags' => 3600,
      'user_suggestions' => 21600,
      'post_stats' => 1800,
    ),
  ),
  'cache_ttl' => 
  array (
    'ttl' => 
    array (
      'ab_test' => 300,
      'cpu_usage' => 60,
      'memory_usage' => 60,
      'active_connections' => 30,
      'queue_size' => 30,
      'critical_assets' => 3600,
      'conversion_funnel' => 3600,
      'conversion_rate' => 7200,
      'server_stats' => 60,
      'localization' => 3600,
      'post' => 300,
      'user_posts' => 300,
      'following' => 600,
      'timeline' => 300,
      'popular_content' => 1800,
      'search' => 600,
      'trending' => 3600,
      'engagement' => 300,
    ),
  ),
  'cors' => 
  array (
    'paths' => 
    array (
      0 => 'api/*',
      1 => 'sanctum/csrf-cookie',
    ),
    'allowed_methods' => 
    array (
      0 => 'GET',
      1 => 'POST',
      2 => 'PUT',
      3 => 'PATCH',
      4 => 'DELETE',
      5 => 'OPTIONS',
    ),
    'allowed_origins' => 
    array (
      0 => 'http://localhost:3000',
    ),
    'allowed_origins_patterns' => 
    array (
    ),
    'allowed_headers' => 
    array (
      0 => 'Content-Type',
      1 => 'X-Requested-With',
      2 => 'Authorization',
      3 => 'Accept',
      4 => 'Origin',
      5 => 'X-CSRF-TOKEN',
    ),
    'exposed_headers' => 
    array (
      0 => 'X-RateLimit-Limit',
      1 => 'X-RateLimit-Remaining',
    ),
    'max_age' => 86400,
    'supports_credentials' => true,
  ),
  'database' => 
  array (
    'default' => 'mysql',
    'connections' => 
    array (
      'sqlite' => 
      array (
        'driver' => 'sqlite',
        'url' => NULL,
        'database' => 'clevlance_db',
        'prefix' => '',
        'foreign_key_constraints' => true,
        'busy_timeout' => NULL,
        'journal_mode' => NULL,
        'synchronous' => NULL,
        'transaction_mode' => 'DEFERRED',
      ),
      'mysql' => 
      array (
        'driver' => 'mysql',
        'url' => NULL,
        'read' => 
        array (
          'host' => 
          array (
            0 => '127.0.0.1',
            1 => '127.0.0.1',
          ),
        ),
        'write' => 
        array (
          'host' => 
          array (
            0 => '127.0.0.1',
          ),
        ),
        'sticky' => true,
        'port' => '3306',
        'database' => 'clevlance_db',
        'username' => 'root',
        'password' => '',
        'unix_socket' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => NULL,
        'options' => 
        array (
        ),
      ),
      'mariadb' => 
      array (
        'driver' => 'mariadb',
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'clevlance_db',
        'username' => 'root',
        'password' => '',
        'unix_socket' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => NULL,
        'options' => 
        array (
        ),
      ),
      'pgsql' => 
      array (
        'driver' => 'pgsql',
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'clevlance_db',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
        'search_path' => 'public',
        'sslmode' => 'prefer',
      ),
      'sqlsrv' => 
      array (
        'driver' => 'sqlsrv',
        'url' => NULL,
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'clevlance_db',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,
      ),
    ),
    'migrations' => 
    array (
      'table' => 'migrations',
      'update_date_on_publish' => true,
    ),
    'redis' => 
    array (
      'client' => 'phpredis',
      'options' => 
      array (
        'cluster' => false,
        'prefix' => 'clevlance-database-',
        'persistent' => false,
      ),
      'clusters' => 
      array (
        'default' => 
        array (
          0 => 
          array (
            'host' => '127.0.0.1',
            'port' => 7000,
            'password' => NULL,
          ),
          1 => 
          array (
            'host' => '127.0.0.1',
            'port' => 7001,
            'password' => NULL,
          ),
          2 => 
          array (
            'host' => '127.0.0.1',
            'port' => 7002,
            'password' => NULL,
          ),
        ),
      ),
      'default' => 
      array (
        'url' => NULL,
        'host' => '127.0.0.1',
        'username' => NULL,
        'password' => NULL,
        'port' => '6379',
        'database' => '0',
        'max_retries' => 3,
        'backoff_algorithm' => 'decorrelated_jitter',
        'backoff_base' => 100,
        'backoff_cap' => 1000,
      ),
      'cache' => 
      array (
        'url' => NULL,
        'host' => '127.0.0.1',
        'username' => NULL,
        'password' => NULL,
        'port' => '6379',
        'database' => '1',
        'max_retries' => 3,
        'backoff_algorithm' => 'decorrelated_jitter',
        'backoff_base' => 100,
        'backoff_cap' => 1000,
      ),
    ),
    'shards_count' => 4,
    'sharding_enabled' => false,
  ),
  'enhancements' => 
  array (
    'elasticsearch' => 
    array (
      'host' => 'localhost:9200',
      'index' => 'clevlance',
      'username' => NULL,
      'password' => NULL,
    ),
    'cdn' => 
    array (
      'enabled' => false,
      'endpoints' => 
      array (
        'images' => 'https://cdn-images.clevlance.com',
        'videos' => 'https://cdn-videos.clevlance.com',
        'static' => 'https://cdn-static.clevlance.com',
      ),
      'aws' => 
      array (
        'cloudfront_distribution_id' => NULL,
      ),
    ),
    'graphql' => 
    array (
      'enabled' => true,
      'endpoint' => '/graphql',
      'playground' => false,
    ),
  ),
  'filesystems' => 
  array (
    'default' => 'local',
    'disks' => 
    array (
      'local' => 
      array (
        'driver' => 'local',
        'root' => 'C:\\Users\\Venus\\Desktop\\wonderway\\backend\\storage\\app/private',
        'serve' => true,
        'throw' => false,
        'report' => false,
      ),
      'public' => 
      array (
        'driver' => 'local',
        'root' => 'C:\\Users\\Venus\\Desktop\\wonderway\\backend\\storage\\app/public',
        'url' => 'http://localhost:8000/storage',
        'visibility' => 'public',
        'throw' => false,
        'report' => false,
      ),
      's3' => 
      array (
        'driver' => 's3',
        'key' => '',
        'secret' => '',
        'region' => 'us-east-1',
        'bucket' => '',
        'url' => NULL,
        'endpoint' => NULL,
        'use_path_style_endpoint' => false,
        'throw' => false,
        'report' => false,
      ),
    ),
    'links' => 
    array (
      'C:\\Users\\Venus\\Desktop\\wonderway\\backend\\public\\storage' => 'C:\\Users\\Venus\\Desktop\\wonderway\\backend\\storage\\app/public',
    ),
  ),
  'limits' => 
  array (
    'rate_limits' => 
    array (
      'auth' => 
      array (
        'login' => '5,1',
        'register' => '10,1',
        'device_verification' => '5,1',
        'resend_code' => '3,1',
        'password_reset' => '5,1',
        'captcha_after_failed' => 3,
      ),
      'social' => 
      array (
        'follow' => '400,1440',
        'block' => '10,1',
        'mute' => '20,1',
        'report' => '5,1',
      ),
      'search' => 
      array (
        'posts' => '450,15',
        'users' => '180,15',
        'hashtags' => '180,15',
        'all' => '450,15',
        'advanced' => '180,15',
        'suggestions' => '180,15',
      ),
      'trending' => 
      array (
        'default' => '75,15',
        'hashtags' => '75,15',
        'posts' => '75,15',
        'users' => '75,15',
        'personalized' => '75,15',
        'velocity' => '180,15',
        'all' => '75,15',
        'stats' => '180,15',
        'refresh' => '15,15',
      ),
      'hashtags' => 
      array (
        'trending' => '75,15',
        'search' => '180,15',
        'show' => '900,15',
        'suggestions' => '180,15',
      ),
      'messaging' => 
      array (
        'send' => '60,1',
      ),
      'polls' => 
      array (
        'create' => '10,1',
        'vote' => '20,1',
        'results' => '60,1',
        'delete' => '10,1',
      ),
      'moderation' => 
      array (
        'report' => '10,1',
      ),
      'mentions' => 
      array (
        'search' => '180,15',
        'view' => '180,15',
      ),
      'realtime' => 
      array (
        'default' => '60,1',
      ),
    ),
    'trending' => 
    array (
      'thresholds' => 
      array (
        'hashtag_min_posts' => 5,
        'post_min_engagement' => 10,
        'user_min_followers' => 100,
      ),
    ),
  ),
  'logging' => 
  array (
    'default' => 'stack',
    'deprecations' => 
    array (
      'channel' => NULL,
      'trace' => false,
    ),
    'channels' => 
    array (
      'stack' => 
      array (
        'driver' => 'stack',
        'channels' => 
        array (
          0 => 'single',
        ),
        'ignore_exceptions' => false,
      ),
      'single' => 
      array (
        'driver' => 'single',
        'path' => 'C:\\Users\\Venus\\Desktop\\wonderway\\backend\\storage\\logs/laravel.log',
        'level' => 'error',
        'replace_placeholders' => true,
      ),
      'daily' => 
      array (
        'driver' => 'daily',
        'path' => 'C:\\Users\\Venus\\Desktop\\wonderway\\backend\\storage\\logs/laravel.log',
        'level' => 'error',
        'days' => 14,
        'replace_placeholders' => true,
      ),
      'slack' => 
      array (
        'driver' => 'slack',
        'url' => NULL,
        'username' => 'Laravel Log',
        'emoji' => ':boom:',
        'level' => 'error',
        'replace_placeholders' => true,
      ),
      'papertrail' => 
      array (
        'driver' => 'monolog',
        'level' => 'error',
        'handler' => 'Monolog\\Handler\\SyslogUdpHandler',
        'handler_with' => 
        array (
          'host' => NULL,
          'port' => NULL,
          'connectionString' => 'tls://:',
        ),
        'processors' => 
        array (
          0 => 'Monolog\\Processor\\PsrLogMessageProcessor',
        ),
      ),
      'stderr' => 
      array (
        'driver' => 'monolog',
        'level' => 'error',
        'handler' => 'Monolog\\Handler\\StreamHandler',
        'handler_with' => 
        array (
          'stream' => 'php://stderr',
        ),
        'formatter' => NULL,
        'processors' => 
        array (
          0 => 'Monolog\\Processor\\PsrLogMessageProcessor',
        ),
      ),
      'syslog' => 
      array (
        'driver' => 'syslog',
        'level' => 'error',
        'facility' => 8,
        'replace_placeholders' => true,
      ),
      'errorlog' => 
      array (
        'driver' => 'errorlog',
        'level' => 'error',
        'replace_placeholders' => true,
      ),
      'null' => 
      array (
        'driver' => 'monolog',
        'handler' => 'Monolog\\Handler\\NullHandler',
      ),
      'emergency' => 
      array (
        'path' => 'C:\\Users\\Venus\\Desktop\\wonderway\\backend\\storage\\logs/laravel.log',
      ),
      'security' => 
      array (
        'driver' => 'daily',
        'path' => 'C:\\Users\\Venus\\Desktop\\wonderway\\backend\\storage\\logs/security.log',
        'level' => 'error',
        'days' => 30,
        'replace_placeholders' => true,
      ),
      'performance' => 
      array (
        'driver' => 'daily',
        'path' => 'C:\\Users\\Venus\\Desktop\\wonderway\\backend\\storage\\logs/performance.log',
        'level' => 'error',
        'days' => 7,
        'replace_placeholders' => true,
      ),
    ),
  ),
  'mail' => 
  array (
    'default' => 'smtp',
    'mailers' => 
    array (
      'smtp' => 
      array (
        'transport' => 'smtp',
        'scheme' => NULL,
        'url' => NULL,
        'host' => 'mailpit',
        'port' => '1025',
        'username' => NULL,
        'password' => NULL,
        'timeout' => NULL,
        'local_domain' => 'localhost',
      ),
      'ses' => 
      array (
        'transport' => 'ses',
      ),
      'postmark' => 
      array (
        'transport' => 'postmark',
      ),
      'resend' => 
      array (
        'transport' => 'resend',
      ),
      'sendmail' => 
      array (
        'transport' => 'sendmail',
        'path' => '/usr/sbin/sendmail -bs -i',
      ),
      'log' => 
      array (
        'transport' => 'log',
        'channel' => NULL,
      ),
      'array' => 
      array (
        'transport' => 'array',
      ),
      'failover' => 
      array (
        'transport' => 'failover',
        'mailers' => 
        array (
          0 => 'smtp',
          1 => 'log',
        ),
        'retry_after' => 60,
      ),
      'roundrobin' => 
      array (
        'transport' => 'roundrobin',
        'mailers' => 
        array (
          0 => 'ses',
          1 => 'postmark',
        ),
        'retry_after' => 60,
      ),
    ),
    'from' => 
    array (
      'address' => 'hello@clevlance.com',
      'name' => 'Clevlance',
    ),
    'markdown' => 
    array (
      'theme' => 'default',
      'paths' => 
      array (
        0 => 'C:\\Users\\Venus\\Desktop\\wonderway\\backend\\resources\\views/vendor/mail',
      ),
    ),
  ),
  'media' => 
  array (
    'max_file_size' => 
    array (
      'image' => 5242880,
      'video' => 2147483648,
      'gif' => 15728640,
      'document' => 10485760,
    ),
    'allowed_mime_types' => 
    array (
      'image' => 
      array (
        0 => 'image/jpeg',
        1 => 'image/png',
        2 => 'image/gif',
        3 => 'image/webp',
      ),
      'video' => 
      array (
        0 => 'video/mp4',
        1 => 'video/quicktime',
        2 => 'video/x-msvideo',
      ),
      'document' => 
      array (
        0 => 'application/pdf',
        1 => 'application/msword',
        2 => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      ),
    ),
    'image_dimensions' => 
    array (
      'avatar' => 
      array (
        'width' => 400,
        'height' => 400,
      ),
      'cover' => 
      array (
        'width' => 1200,
        'height' => 400,
      ),
      'story' => 
      array (
        'width' => 1080,
        'height' => 1920,
      ),
      'post' => 
      array (
        'max_width' => 4096,
        'max_height' => 4096,
      ),
    ),
    'video_dimensions' => 
    array (
      'max_width' => 1920,
      'max_height' => 1200,
      'max_duration' => 140,
    ),
    'image_variants' => 
    array (
      'small' => 340,
      'medium' => 680,
      'large' => 1200,
    ),
    'video_qualities' => 
    array (
      '240p' => 
      array (
        'width' => 426,
        'height' => 240,
        'bitrate' => 500,
      ),
      '360p' => 
      array (
        'width' => 640,
        'height' => 360,
        'bitrate' => 800,
      ),
      '480p' => 
      array (
        'width' => 854,
        'height' => 480,
        'bitrate' => 1000,
      ),
      '720p' => 
      array (
        'width' => 1280,
        'height' => 720,
        'bitrate' => 2500,
      ),
      '1080p' => 
      array (
        'width' => 1920,
        'height' => 1080,
        'bitrate' => 5000,
      ),
    ),
    'quality' => 
    array (
      'image' => 85,
      'thumbnail' => 80,
    ),
    'storage_disk' => 'public',
  ),
  'monetization' => 
  array (
    'roles' => 
    array (
      'user' => 
      array (
        'media_per_post' => 4,
        'max_file_size_kb' => 5120,
        'posts_per_day' => 100,
        'video_length_seconds' => 140,
        'scheduled_posts' => 0,
        'rate_limit_per_minute' => 60,
        'hd_upload' => false,
        'advertisements' => false,
      ),
      'verified' => 
      array (
        'media_per_post' => 4,
        'max_file_size_kb' => 10240,
        'posts_per_day' => 200,
        'video_length_seconds' => 140,
        'scheduled_posts' => 0,
        'rate_limit_per_minute' => 100,
        'hd_upload' => false,
        'advertisements' => false,
      ),
      'premium' => 
      array (
        'media_per_post' => 10,
        'max_file_size_kb' => 51200,
        'posts_per_day' => 500,
        'video_length_seconds' => 600,
        'scheduled_posts' => 100,
        'rate_limit_per_minute' => 200,
        'hd_upload' => true,
        'advertisements' => false,
      ),
      'organization' => 
      array (
        'media_per_post' => 10,
        'max_file_size_kb' => 102400,
        'posts_per_day' => 1000,
        'video_length_seconds' => 600,
        'scheduled_posts' => 500,
        'rate_limit_per_minute' => 300,
        'hd_upload' => true,
        'advertisements' => true,
      ),
      'moderator' => 
      array (
        'media_per_post' => 10,
        'max_file_size_kb' => 51200,
        'posts_per_day' => 500,
        'video_length_seconds' => 600,
        'scheduled_posts' => 100,
        'rate_limit_per_minute' => 200,
        'hd_upload' => true,
        'advertisements' => false,
      ),
      'admin' => 
      array (
        'media_per_post' => 20,
        'max_file_size_kb' => 204800,
        'posts_per_day' => 2000,
        'video_length_seconds' => 1200,
        'scheduled_posts' => 1000,
        'rate_limit_per_minute' => 500,
        'hd_upload' => true,
        'advertisements' => true,
      ),
    ),
    'creator_fund' => 
    array (
      'base_rate' => 0.001,
      'max_engagement_multiplier' => 0.1,
      'min_views' => 10000,
      'min_quality_score' => 70,
      'min_followers' => 1000,
    ),
    'advertisements' => 
    array (
      'default_cost_per_click' => 0.1,
      'default_cost_per_impression' => 0.01,
    ),
  ),
  'pagination' => 
  array (
    'default' => 20,
    'posts' => 20,
    'messages' => 50,
    'notifications' => 20,
    'users' => 20,
    'comments' => 20,
    'bookmarks' => 20,
    'communities' => 20,
    'follows' => 20,
    'hashtags' => 20,
    'lists' => 20,
    'reports' => 20,
    'reposts' => 20,
    'likes' => 20,
    'trending' => 10,
    'suggestions' => 10,
    'search' => 20,
    'activities' => 50,
    'cache_warmup' => 100,
  ),
  'performance' => 
  array (
    'monitoring' => 
    array (
      'simulation_delay_seconds' => 0.05,
    ),
    'email' => 
    array (
      'rate_limit_delay_seconds' => 5,
    ),
  ),
  'permission' => 
  array (
    'models' => 
    array (
      'permission' => 'Spatie\\Permission\\Models\\Permission',
      'role' => 'Spatie\\Permission\\Models\\Role',
    ),
    'table_names' => 
    array (
      'roles' => 'roles',
      'permissions' => 'permissions',
      'model_has_permissions' => 'model_has_permissions',
      'model_has_roles' => 'model_has_roles',
      'role_has_permissions' => 'role_has_permissions',
    ),
    'column_names' => 
    array (
      'role_pivot_key' => NULL,
      'permission_pivot_key' => NULL,
      'model_morph_key' => 'model_id',
      'team_foreign_key' => 'team_id',
    ),
    'register_permission_check_method' => true,
    'register_octane_reset_listener' => false,
    'events_enabled' => false,
    'teams' => false,
    'team_resolver' => 'Spatie\\Permission\\DefaultTeamResolver',
    'use_passport_client_credentials' => false,
    'display_permission_in_exception' => false,
    'display_role_in_exception' => false,
    'enable_wildcard_permission' => false,
    'cache' => 
    array (
      'expiration_time' => 
      \DateInterval::__set_state(array(
         'from_string' => true,
         'date_string' => '24 hours',
      )),
      'key' => 'spatie.permission.cache',
      'store' => 'default',
    ),
  ),
  'polls' => 
  array (
    'max_question_length' => 200,
    'min_options' => 2,
    'max_options' => 4,
    'max_option_length' => 100,
    'min_duration_hours' => 1,
    'max_duration_hours' => 168,
  ),
  'posts' => 
  array (
    'edit_timeout_minutes' => 60,
    'max_thread_posts' => 25,
  ),
  'queue' => 
  array (
    'default' => 'redis',
    'connections' => 
    array (
      'sync' => 
      array (
        'driver' => 'sync',
      ),
      'database' => 
      array (
        'driver' => 'database',
        'connection' => NULL,
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 90,
        'after_commit' => false,
      ),
      'beanstalkd' => 
      array (
        'driver' => 'beanstalkd',
        'host' => 'localhost',
        'queue' => 'default',
        'retry_after' => 90,
        'block_for' => 0,
        'after_commit' => false,
      ),
      'sqs' => 
      array (
        'driver' => 'sqs',
        'key' => '',
        'secret' => '',
        'prefix' => 'https://sqs.us-east-1.amazonaws.com/your-account-id',
        'queue' => 'default',
        'suffix' => NULL,
        'region' => 'us-east-1',
        'after_commit' => false,
      ),
      'redis' => 
      array (
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'default',
        'retry_after' => 90,
        'block_for' => NULL,
        'after_commit' => false,
      ),
      'deferred' => 
      array (
        'driver' => 'deferred',
      ),
      'failover' => 
      array (
        'driver' => 'failover',
        'connections' => 
        array (
          0 => 'database',
          1 => 'deferred',
        ),
      ),
      'background' => 
      array (
        'driver' => 'background',
      ),
    ),
    'batching' => 
    array (
      'database' => 'mysql',
      'table' => 'job_batches',
    ),
    'failed' => 
    array (
      'driver' => 'database-uuids',
      'database' => 'mysql',
      'table' => 'failed_jobs',
    ),
    'defaults' => 
    array (
      'tries' => 3,
      'timeout' => 120,
      'backoff' => 
      array (
        0 => 30,
        1 => 60,
        2 => 120,
      ),
      'jobs' => 
      array (
        'thumbnail' => 
        array (
          'tries' => 3,
          'timeout' => 120,
        ),
        'email' => 
        array (
          'tries' => 3,
          'timeout' => 300,
          'backoff' => 
          array (
            0 => 30,
            1 => 60,
            2 => 120,
          ),
        ),
        'cleanup' => 
        array (
          'tries' => 3,
          'timeout' => 300,
        ),
      ),
    ),
    'names' => 
    array (
      'high' => 'high',
      'default' => 'default',
      'low' => 'low',
      'image_processing' => 'image-processing',
    ),
  ),
  'reverb' => 
  array (
    'default' => 'reverb',
    'servers' => 
    array (
      'reverb' => 
      array (
        'host' => '0.0.0.0',
        'port' => 8080,
        'path' => '',
        'hostname' => 'localhost',
        'options' => 
        array (
          'tls' => 
          array (
          ),
        ),
        'max_request_size' => 10000,
        'scaling' => 
        array (
          'enabled' => false,
          'channel' => 'reverb',
          'server' => 
          array (
            'url' => NULL,
            'host' => '127.0.0.1',
            'port' => '6379',
            'username' => NULL,
            'password' => NULL,
            'database' => '0',
            'timeout' => 60,
          ),
        ),
        'pulse_ingest_interval' => 15,
        'telescope_ingest_interval' => 15,
      ),
    ),
    'apps' => 
    array (
      'provider' => 'config',
      'apps' => 
      array (
        0 => 
        array (
          'key' => '',
          'secret' => '',
          'app_id' => '',
          'options' => 
          array (
            'host' => 'localhost',
            'port' => '8080',
            'scheme' => 'http',
            'useTLS' => false,
          ),
          'allowed_origins' => 
          array (
            0 => '*',
          ),
          'ping_interval' => 60,
          'activity_timeout' => 30,
          'max_connections' => NULL,
          'max_message_size' => 10000,
        ),
      ),
    ),
  ),
  'sanctum' => 
  array (
    'stateful' => 
    array (
      0 => 'localhost',
      1 => 'localhost:3000',
      2 => '127.0.0.1',
      3 => '127.0.0.1:8000',
      4 => '::1',
      5 => 'localhost:8000',
    ),
    'guard' => 
    array (
      0 => 'web',
    ),
    'expiration' => 120,
    'token_prefix' => '',
    'middleware' => 
    array (
      'authenticate_session' => 'Laravel\\Sanctum\\Http\\Middleware\\AuthenticateSession',
      'encrypt_cookies' => 'Illuminate\\Cookie\\Middleware\\EncryptCookies',
      'validate_csrf_token' => 'Illuminate\\Foundation\\Http\\Middleware\\ValidateCsrfToken',
    ),
  ),
  'scout' => 
  array (
    'driver' => 'meilisearch',
    'prefix' => '',
    'queue' => false,
    'after_commit' => false,
    'chunk' => 
    array (
      'searchable' => 500,
      'unsearchable' => 500,
    ),
    'soft_delete' => false,
    'identify' => false,
    'algolia' => 
    array (
      'id' => '',
      'secret' => '',
      'index-settings' => 
      array (
      ),
    ),
    'meilisearch' => 
    array (
      'host' => 'http://127.0.0.1:7700',
      'key' => 'masterKey123',
      'index-settings' => 
      array (
      ),
    ),
    'typesense' => 
    array (
      'client-settings' => 
      array (
        'api_key' => 'xyz',
        'nodes' => 
        array (
          0 => 
          array (
            'host' => 'localhost',
            'port' => '8108',
            'path' => '',
            'protocol' => 'http',
          ),
        ),
        'nearest_node' => 
        array (
          'host' => 'localhost',
          'port' => '8108',
          'path' => '',
          'protocol' => 'http',
        ),
        'connection_timeout_seconds' => 2,
        'healthcheck_interval_seconds' => 30,
        'num_retries' => 3,
        'retry_interval_seconds' => 1,
      ),
      'model-settings' => 
      array (
      ),
      'import_action' => 'upsert',
    ),
  ),
  'security' => 
  array (
    'password' => 
    array (
      'reset' => 
      array (
        'expire_minutes' => 15,
        'throttle_seconds' => 60,
      ),
      'security' => 
      array (
        'min_length' => 8,
        'require_letters' => true,
        'require_numbers' => true,
        'require_special_chars' => false,
        'history_limit' => 5,
        'min_age_hours' => 1,
        'max_age_days' => 90,
      ),
      'strength_scores' => 
      array (
        'length_multiplier' => 2,
        'max_length_bonus' => 25,
        'letter_bonus' => 10,
        'number_bonus' => 10,
        'special_char_bonus' => 10,
        'mixed_case_bonus' => 5,
        'repeated_penalty' => 10,
        'sequential_penalty' => 10,
        'common_password_penalty' => 25,
        'max_score' => 100,
      ),
    ),
    'tokens' => 
    array (
      'access_lifetime_seconds' => 7200,
      'refresh_lifetime_seconds' => 604800,
      'refresh_token_length' => 60,
      'remember_lifetime_seconds' => 1209600,
      'auto_refresh_threshold' => 300,
    ),
    'session' => 
    array (
      'timeout_seconds' => 7200,
      'concurrent_limit' => 3,
      'fingerprint_validation' => true,
    ),
    'email' => 
    array (
      'verification_expire_minutes' => 15,
      'code_length' => 6,
      'code_min' => 100000,
      'code_max' => 999999,
      'verification_token_length' => 60,
      'max_code_attempts' => 5,
      'blacklist_domains' => 
      array (
        0 => '10minutemail.com',
        1 => 'tempmail.org',
        2 => 'guerrillamail.com',
      ),
      'templates' => 
      array (
        'brand_color' => '#1DA1F2',
        'logo_url' => NULL,
        'support_email' => 'support@localhost',
      ),
    ),
    'device' => 
    array (
      'verification_enabled' => true,
      'max_devices' => 5,
      'max_inactivity_days' => 30,
      'token_length' => 40,
      'fingerprint_components' => 
      array (
        0 => 'user_agent',
        1 => 'accept_language',
        2 => 'ip_address',
      ),
    ),
    'social' => 
    array (
      'password_length' => 32,
      'google' => 
      array (
        'client_id' => '',
        'client_secret' => '',
        'redirect' => 'http://localhost:8000/auth/google/callback',
      ),
      'apple' => 
      array (
        'client_id' => NULL,
        'client_secret' => NULL,
        'redirect' => NULL,
      ),
    ),
    'age_restrictions' => 
    array (
      'minimum_age' => 15,
      'child_age_threshold' => 18,
    ),
    'http_status' => 
    array (
      'unauthorized' => 401,
      'forbidden' => 403,
      'not_found' => 404,
      'csrf_token_mismatch' => 419,
      'unprocessable_entity' => 422,
      'too_many_requests' => 429,
      'internal_server_error' => 500,
      'service_unavailable' => 503,
    ),
    'threat_detection' => 
    array (
      'scores' => 
      array (
        'sql_injection' => 50,
        'xss' => 40,
        'bot' => 30,
      ),
      'thresholds' => 
      array (
        'block' => 80,
        'challenge' => 60,
        'monitor' => 40,
      ),
      'ip_block_duration' => 3600,
    ),
    'bot_detection' => 
    array (
      'scores' => 
      array (
        'bot_user_agent' => 50,
        'rapid_requests' => 30,
        'suspicious_headers' => 20,
        'suspicious_behavior' => 25,
        'no_javascript' => 15,
        'known_bot_fingerprint' => 40,
      ),
      'thresholds' => 
      array (
        'block' => 90,
        'challenge' => 70,
        'monitor' => 50,
      ),
      'rapid_requests' => 
      array (
        'max_requests' => 10,
        'window_seconds' => 10,
      ),
      'behavior' => 
      array (
        'max_same_page_requests' => 20,
        'min_unique_pages' => 3,
        'min_time_per_page' => 2,
      ),
      'challenge_retry_after' => 30,
      'known_bot_cache_days' => 7,
    ),
    'monitoring' => 
    array (
      'alert_thresholds' => 
      array (
        'failed_logins' => 10,
        'blocked_requests' => 50,
        'suspicious_activities' => 5,
        'data_breaches' => 1,
        'privilege_escalations' => 1,
      ),
      'risk_levels' => 
      array (
        'high' => 50,
        'medium' => 30,
      ),
      'risk_scores' => 
      array (
        'new_ip' => 20,
        'high_activity' => 30,
        'failed_logins' => 30,
        'unusual_hours' => 15,
      ),
      'unusual_hours' => 
      array (
        'start' => 23,
        'end' => 6,
      ),
      'failed_login_threshold' => 3,
    ),
    'rate_limiting' => 
    array (
      'lock_timeout' => 5,
      'default_remaining' => 999,
      'default_retry_after' => 60,
      'default_window' => 60,
      'auth' => 
      array (
        'login' => 
        array (
          'max_attempts' => 5,
          'window_minutes' => 1,
        ),
        'register' => 
        array (
          'max_attempts' => 3,
          'window_minutes' => 1,
        ),
        'password_reset' => 
        array (
          'max_attempts' => 3,
          'window_minutes' => 1,
        ),
        'email_verify' => 
        array (
          'max_attempts' => 10,
          'window_minutes' => 1,
        ),
        'device_verification' => 
        array (
          'max_attempts' => 5,
          'window_minutes' => 1,
        ),
      ),
      'social' => 
      array (
        'follow' => 
        array (
          'max_attempts' => 400,
          'window_minutes' => 1440,
        ),
        'block' => 
        array (
          'max_attempts' => 10,
          'window_minutes' => 1,
        ),
        'mute' => 
        array (
          'max_attempts' => 20,
          'window_minutes' => 1,
        ),
      ),
      'search' => 
      array (
        'posts' => 
        array (
          'max_attempts' => 450,
          'window_minutes' => 15,
        ),
        'users' => 
        array (
          'max_attempts' => 180,
          'window_minutes' => 15,
        ),
        'hashtags' => 
        array (
          'max_attempts' => 180,
          'window_minutes' => 15,
        ),
        'all' => 
        array (
          'max_attempts' => 180,
          'window_minutes' => 15,
        ),
        'advanced' => 
        array (
          'max_attempts' => 180,
          'window_minutes' => 15,
        ),
        'suggestions' => 
        array (
          'max_attempts' => 180,
          'window_minutes' => 15,
        ),
      ),
      'messaging' => 
      array (
        'send' => 
        array (
          'max_attempts' => 60,
          'window_minutes' => 1,
        ),
      ),
      'hashtags' => 
      array (
        'trending' => 
        array (
          'max_attempts' => 75,
          'window_minutes' => 15,
        ),
        'search' => 
        array (
          'max_attempts' => 180,
          'window_minutes' => 15,
        ),
        'suggestions' => 
        array (
          'max_attempts' => 180,
          'window_minutes' => 15,
        ),
        'show' => 
        array (
          'max_attempts' => 900,
          'window_minutes' => 15,
        ),
      ),
      'trending' => 
      array (
        'hashtags' => 
        array (
          'max_attempts' => 75,
          'window_minutes' => 15,
        ),
        'posts' => 
        array (
          'max_attempts' => 75,
          'window_minutes' => 15,
        ),
        'users' => 
        array (
          'max_attempts' => 75,
          'window_minutes' => 15,
        ),
        'personalized' => 
        array (
          'max_attempts' => 75,
          'window_minutes' => 15,
        ),
        'velocity' => 
        array (
          'max_attempts' => 180,
          'window_minutes' => 15,
        ),
        'all' => 
        array (
          'max_attempts' => 75,
          'window_minutes' => 15,
        ),
        'stats' => 
        array (
          'max_attempts' => 180,
          'window_minutes' => 15,
        ),
        'refresh' => 
        array (
          'max_attempts' => 15,
          'window_minutes' => 15,
        ),
      ),
      'polls' => 
      array (
        'create' => 
        array (
          'max_attempts' => 10,
          'window_minutes' => 1,
        ),
        'vote' => 
        array (
          'max_attempts' => 20,
          'window_minutes' => 1,
        ),
        'results' => 
        array (
          'max_attempts' => 60,
          'window_minutes' => 1,
        ),
        'delete' => 
        array (
          'max_attempts' => 10,
          'window_minutes' => 1,
        ),
      ),
      'moderation' => 
      array (
        'report' => 
        array (
          'max_attempts' => 5,
          'window_minutes' => 1,
        ),
      ),
      'mentions' => 
      array (
        'search' => 
        array (
          'max_attempts' => 60,
          'window_minutes' => 1,
        ),
        'view' => 
        array (
          'max_attempts' => 60,
          'window_minutes' => 1,
        ),
      ),
      'realtime' => 
      array (
        'default' => 
        array (
          'max_attempts' => 60,
          'window_minutes' => 1,
        ),
      ),
    ),
    'captcha' => 
    array (
      'failed_attempts_threshold' => 3,
      'min_score' => 0.5,
    ),
    'file_security' => 
    array (
      'max_size' => 10485760,
      'max_image_dimension' => 4096,
    ),
    'waf' => 
    array (
      'enabled' => true,
      'threat_threshold' => 60,
      'ip_block_duration' => 3600,
      'admin_allowed_ips' => 
      array (
        0 => '127.0.0.1',
        1 => '::1',
      ),
      'require_2fa' => true,
      'headers' => 
      array (
        'enabled' => true,
        'hsts' => 
        array (
          'enabled' => true,
          'max_age' => 31536000,
          'include_subdomains' => true,
          'preload' => true,
        ),
        'csp' => 
        array (
          'enabled' => true,
          'policy' => 'default-src \'self\'; script-src \'self\' \'unsafe-inline\'; style-src \'self\' \'unsafe-inline\'',
        ),
        'x_frame_options' => 'DENY',
        'x_content_type_options' => 'nosniff',
        'x_xss_protection' => '1; mode=block',
        'referrer_policy' => 'strict-origin-when-cross-origin',
      ),
    ),
    'cache' => 
    array (
      'last_seen' => 60,
      'bot_requests' => 600,
      'bot_behavior' => 3600,
    ),
    'spam' => 
    array (
      'thresholds' => 
      array (
        'post' => 70,
        'comment' => 60,
        'user' => 50,
      ),
      'penalties' => 
      array (
        'spam_keyword' => 20,
        'multiple_links_high' => 50,
        'multiple_links_medium' => 25,
        'single_link' => 10,
        'suspicious_pattern' => 15,
        'short_content' => 10,
        'excessive_emoji' => 15,
        'new_account' => 20,
        'multiple_reports' => 25,
        'flagged_user' => 30,
        'suspicious_follower_ratio' => 15,
        'high_frequency' => 30,
        'medium_frequency' => 15,
        'duplicate_content' => 25,
      ),
      'limits' => 
      array (
        'url_count_high' => 3,
        'url_count_medium' => 2,
        'min_content_length' => 10,
        'max_emoji_count' => 10,
        'new_user_days' => 1,
        'report_threshold' => 5,
        'following_threshold' => 100,
        'follower_threshold' => 10,
        'posts_per_hour_high' => 10,
        'posts_per_hour_medium' => 5,
      ),
    ),
  ),
  'services' => 
  array (
    'postmark' => 
    array (
      'token' => NULL,
    ),
    'resend' => 
    array (
      'key' => NULL,
    ),
    'ses' => 
    array (
      'key' => '',
      'secret' => '',
      'region' => 'us-east-1',
    ),
    'slack' => 
    array (
      'notifications' => 
      array (
        'bot_user_oauth_token' => NULL,
        'channel' => NULL,
      ),
    ),
    'twilio' => 
    array (
      'account_sid' => NULL,
      'auth_token' => '',
      'phone_number' => '',
    ),
    'firebase' => 
    array (
      'api_key' => NULL,
      'project_id' => NULL,
      'credentials' => NULL,
    ),
    'sendgrid' => 
    array (
      'api_key' => NULL,
      'from_email' => NULL,
    ),
    'google' => 
    array (
      'client_id' => NULL,
      'client_secret' => NULL,
      'redirect' => NULL,
    ),
    'recaptcha' => 
    array (
      'site_key' => NULL,
      'secret_key' => NULL,
    ),
    'analytics' => 
    array (
      'event_types' => 
      array (
        'engagement' => 
        array (
          0 => 'post_like',
          1 => 'post_comment',
          2 => 'post_repost',
        ),
        'post_engagement' => 
        array (
          0 => 'post_like',
          1 => 'post_comment',
          2 => 'post_repost',
          3 => 'post_share',
          4 => 'link_click',
        ),
        'active_user' => 
        array (
          0 => 'login',
          1 => 'post_create',
          2 => 'comment',
          3 => 'like',
        ),
      ),
    ),
  ),
  'session' => 
  array (
    'driver' => 'redis',
    'lifetime' => 120,
    'expire_on_close' => false,
    'encrypt' => true,
    'files' => 'C:\\Users\\Venus\\Desktop\\wonderway\\backend\\storage\\framework/sessions',
    'connection' => NULL,
    'table' => 'sessions',
    'store' => NULL,
    'lottery' => 
    array (
      0 => 2,
      1 => 100,
    ),
    'cookie' => 'clevlance-session',
    'path' => '/',
    'domain' => NULL,
    'secure' => true,
    'http_only' => true,
    'same_site' => 'strict',
    'partitioned' => false,
  ),
  'status' => 
  array (
    'ab_test' => 
    array (
      'active' => 'active',
      'paused' => 'paused',
      'completed' => 'completed',
    ),
    'community_join_request' => 
    array (
      'pending' => 'pending',
      'approved' => 'approved',
      'rejected' => 'rejected',
    ),
    'community_note' => 
    array (
      'pending' => 'pending',
      'approved' => 'approved',
      'rejected' => 'rejected',
    ),
    'report' => 
    array (
      'pending' => 'pending',
      'resolved' => 'resolved',
      'dismissed' => 'dismissed',
    ),
    'scheduled_post' => 
    array (
      'pending' => 'pending',
      'published' => 'published',
      'failed' => 'failed',
    ),
    'space' => 
    array (
      'live' => 'live',
      'ended' => 'ended',
      'scheduled' => 'scheduled',
    ),
    'space_participant' => 
    array (
      'invited' => 'invited',
      'joined' => 'joined',
      'left' => 'left',
    ),
    'subscription' => 
    array (
      'active' => 'active',
      'cancelled' => 'cancelled',
      'expired' => 'expired',
    ),
  ),
  'validation' => 
  array (
    'user' => 
    array (
      'name' => 
      array (
        'max_length' => 50,
      ),
      'email' => 
      array (
        'max_length' => 255,
      ),
      'bio' => 
      array (
        'max_length' => 500,
      ),
      'location' => 
      array (
        'max_length' => 100,
      ),
      'website' => 
      array (
        'max_length' => 255,
      ),
    ),
    'password' => 
    array (
      'min_length' => 8,
    ),
    'date' => 
    array (
      'before_rule' => 'before:today',
    ),
    'search' => 
    array (
      'query' => 
      array (
        'min_length' => 2,
        'max_length' => 100,
      ),
      'posts' => 
      array (
        'per_page' => 20,
        'max_per_page' => 100,
      ),
      'users' => 
      array (
        'per_page' => 20,
        'max_per_page' => 100,
      ),
      'hashtags' => 
      array (
        'per_page' => 20,
        'max_per_page' => 100,
      ),
      'rate_limits' => 
      array (
        'posts' => 450,
        'users' => 180,
        'window' => 15,
      ),
    ),
    'trending' => 
    array (
      'limit' => 
      array (
        'default' => 10,
        'max' => 100,
      ),
      'timeframe' => 
      array (
        'default' => 24,
        'max' => 720,
      ),
    ),
    'content' => 
    array (
      'post' => 
      array (
        'max_length' => 280,
        'min_length' => 1,
        'max_links' => 2,
        'max_mentions' => 5,
      ),
      'comment' => 
      array (
        'max_length' => 280,
        'min_length' => 1,
      ),
      'message' => 
      array (
        'max_length' => 1000,
        'min_length' => 1,
      ),
      'community' => 
      array (
        'name_max_length' => 100,
        'description_max_length' => 500,
      ),
    ),
    'file_upload' => 
    array (
      'image' => 
      array (
        'max_size_kb' => 5120,
        'allowed_types' => 
        array (
          0 => 'jpeg',
          1 => 'png',
          2 => 'jpg',
          3 => 'gif',
          4 => 'webp',
        ),
        'allowed_mimes' => 'jpeg,png,jpg,gif,webp',
      ),
      'avatar' => 
      array (
        'max_size_kb' => 2048,
        'allowed_types' => 
        array (
          0 => 'jpeg',
          1 => 'png',
          2 => 'jpg',
          3 => 'gif',
        ),
        'allowed_mimes' => 'jpeg,png,jpg,gif',
      ),
      'video' => 
      array (
        'max_size_kb' => 102400,
        'allowed_types' => 
        array (
          0 => 'mp4',
          1 => 'mov',
          2 => 'avi',
          3 => 'mkv',
          4 => 'webm',
        ),
        'allowed_mimes' => 'mp4,mov,avi,mkv,webm',
      ),
      'media_general' => 
      array (
        'max_size_kb' => 10240,
        'allowed_types' => 
        array (
          0 => 'jpeg',
          1 => 'png',
          2 => 'gif',
          3 => 'webp',
          4 => 'mp4',
          5 => 'mov',
        ),
        'allowed_mimes' => 'jpeg,png,gif,webp,mp4,mov',
      ),
    ),
    'max' => 
    array (
      'name' => 100,
      'title' => 100,
      'description' => 500,
      'content' => 300,
      'url' => 255,
      'token' => 500,
      'reason' => 200,
      'text_short' => 50,
      'text_medium' => 100,
      'text_long' => 200,
      'array_small' => 4,
      'array_medium' => 10,
      'array_large' => 25,
      'age' => 100,
      'percentage' => 100,
      'instances' => 10,
      'sources' => 3,
      'tags' => 5,
      'rules' => 10,
      'media' => 4,
      'attachments' => 10,
      'participants' => 100,
      'poll_options' => 4,
      'thread_posts' => 25,
      'interests' => 10,
      'coupon' => 20,
      'version' => 20,
      'account_number' => 50,
      'routing_number' => 20,
      'alt_text' => 200,
      'banner_size' => 1024,
    ),
    'min' => 
    array (
      'search' => 1,
      'mention' => 2,
      'community_note' => 10,
      'poll_options' => 2,
      'thread_posts' => 2,
      'moment_posts' => 2,
      'age' => 13,
      'instances' => 1,
      'month' => 1,
      'limit' => 1,
      'participants' => 2,
    ),
  ),
  'google2fa' => 
  array (
    'enabled' => true,
    'lifetime' => 0,
    'keep_alive' => true,
    'auth' => 'auth',
    'guard' => '',
    'session_var' => 'google2fa',
    'otp_input' => 'one_time_password',
    'window' => 1,
    'forbid_old_passwords' => false,
    'otp_secret_column' => 'google2fa_secret',
    'view' => 'google2fa.index',
    'error_messages' => 
    array (
      'wrong_otp' => 'The \'One Time Password\' typed was wrong.',
      'cannot_be_empty' => 'One Time Password cannot be empty.',
      'unknown' => 'An unknown error has occurred. Please try again.',
    ),
    'throw_exceptions' => true,
    'qrcode_image_backend' => 'svg',
  ),
  'tinker' => 
  array (
    'commands' => 
    array (
    ),
    'alias' => 
    array (
    ),
    'dont_alias' => 
    array (
      0 => 'App\\Nova',
    ),
    'trust_project' => 'always',
  ),
);
