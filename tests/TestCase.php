<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable CSRF for tests
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        try {
            $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        } catch (\Exception $e) {
            // Ignore seeder errors in tests
        }
    }
}
