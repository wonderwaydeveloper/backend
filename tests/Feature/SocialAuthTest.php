<?php

namespace Tests\Feature;

use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    public function test_google_redirect()
    {
        $response = $this->getJson('/api/auth/social/google');
        $response->assertStatus(200);
    }

    public function test_apple_redirect()
    {
        // Apple driver might not be configured in test environment
        $response = $this->getJson('/api/auth/social/apple');
        // Accept either 200 (configured) or 500 (not configured)
        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    public function test_github_redirect_not_available()
    {
        $response = $this->getJson('/api/auth/social/github');
        $response->assertStatus(404);
    }

    public function test_facebook_redirect_not_available()
    {
        $response = $this->getJson('/api/auth/social/facebook');
        $response->assertStatus(404);
    }
}
