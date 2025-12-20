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

    public function test_github_redirect()
    {
        $response = $this->getJson('/api/auth/social/github');
        $response->assertStatus(200);
    }

    public function test_facebook_redirect()
    {
        $response = $this->getJson('/api/auth/social/facebook');
        $response->assertStatus(200);
    }
}
