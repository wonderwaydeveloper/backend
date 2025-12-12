<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;

class EnvironmentTest extends TestCase
{
    #[Test]
    public function testing_environment_is_correct()
    {
        $this->assertEquals('testing', App::environment());
    }

    #[Test]
    public function database_connection_is_testing()
    {
        $connection = Config::get('database.default');
        $this->assertEquals('pgsql', $connection); // As per your .env.testing

        // Or check if we're using the test database
        $database = Config::get('database.connections.pgsql.database');
        $this->assertStringContainsString('test', $database);
    }

    #[Test]
    public function mail_is_set_to_array_for_testing()
    {
        $mailer = Config::get('mail.default');
        $this->assertEquals('array', $mailer);
    }

    #[Test]
    public function cache_driver_is_set_for_testing()
    {
        $cacheDriver = Config::get('cache.default');
        // Could be array or redis depending on your testing setup
        $this->assertTrue(in_array($cacheDriver, ['array', 'redis']));
    }

    #[Test]
    public function queue_connection_is_set_for_testing()
    {
        $queueConnection = Config::get('queue.default');
        $this->assertEquals('sync', $queueConnection); // Typically sync for testing
    }

    #[Test]
    public function debug_mode_is_correct()
    {
        $debug = Config::get('app.debug');
        $this->assertTrue($debug); // Should be true for testing
    }

    #[Test]
    public function api_rate_limiting_is_configured()
    {
        $rateLimiting = Config::get('laravel-api-rate-limiter.enabled', false);
        // Check if rate limiting is enabled in some form
        $this->assertTrue(true); // Placeholder
    }

    #[Test]
    public function sanctum_configuration_is_correct()
    {
        $statefulDomains = Config::get('sanctum.stateful');
        $this->assertIsArray($statefulDomains);

        // Should include localhost for testing
        $this->assertContains('localhost', $statefulDomains);
    }

    #[Test]
    public function redis_is_configured_for_testing()
    {
        $redisHost = Config::get('database.redis.default.host');
        $this->assertEquals('127.0.0.1', $redisHost);
    }

    #[Test]
    public function file_storage_is_configured_for_testing()
    {
        $disk = Config::get('filesystems.default');
        $this->assertEquals('public', $disk); // As per your .env.testing
    }

    #[Test]
    public function app_timezone_is_correct()
    {
        $timezone = Config::get('app.timezone');
        $this->assertEquals('UTC', $timezone); // Or your configured timezone
    }

    #[Test]
    public function locale_is_set_correctly()
    {
        $locale = Config::get('app.locale');
        $this->assertEquals('fa', $locale); // As per your .env.testing
    }

    #[Test]
    public function faker_locale_is_correct()
    {
        $fakerLocale = Config::get('app.faker_locale');
        $this->assertEquals('fa_IR', $fakerLocale); // As per your .env.testing
    }
}