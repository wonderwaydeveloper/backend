<?php

namespace Tests\Feature;

use App\Models\AnalyticsEvent;
use App\Models\ConversionMetric;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Post $post;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->post = Post::factory()->create(['user_id' => $this->user->id]);
    }

    /** @test */
    public function user_can_view_their_analytics()
    {
        $response = $this->actingAs($this->user)->getJson('/api/analytics/user');
        
        $response->assertOk()
            ->assertJsonStructure([
                'analytics' => [
                    'profile_views',
                    'post_performance',
                    'engagement_metrics',
                    'follower_growth',
                    'top_posts',
                ]
            ]);
    }

    /** @test */
    public function user_can_view_their_post_analytics()
    {
        $response = $this->actingAs($this->user)->getJson("/api/analytics/posts/{$this->post->id}");
        
        $response->assertOk()
            ->assertJsonStructure([
                'post_analytics' => [
                    'views',
                    'engagement',
                    'demographics',
                    'timeline',
                ]
            ]);
    }

    /** @test */
    public function user_cannot_view_other_users_post_analytics()
    {
        $otherUser = User::factory()->create();
        $otherPost = Post::factory()->create(['user_id' => $otherUser->id]);
        
        $response = $this->actingAs($this->user)->getJson("/api/analytics/posts/{$otherPost->id}");
        
        $response->assertForbidden();
    }

    /** @test */
    public function can_track_analytics_event()
    {
        $response = $this->actingAs($this->user)->postJson('/api/analytics/track', [
            'event_type' => 'post_view',
            'entity_type' => 'post',
            'entity_id' => $this->post->id,
        ]);
        
        $response->assertOk();
        $this->assertDatabaseHas('analytics_events', [
            'event_type' => 'post_view',
            'entity_type' => 'post',
            'entity_id' => $this->post->id,
        ]);
    }

    /** @test */
    public function can_track_conversion_event()
    {
        $response = $this->actingAs($this->user)->postJson('/api/conversions/track', [
            'event_type' => 'signup',
            'conversion_value' => 0,
        ]);
        
        $response->assertOk();
        $this->assertDatabaseHas('conversion_metrics', [
            'event_type' => 'signup',
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function can_get_conversion_funnel()
    {
        $response = $this->actingAs($this->user)->getJson('/api/conversions/funnel');
        
        $response->assertOk()
            ->assertJsonStructure([
                'visitors',
                'signups',
                'active_users',
                'premium_subscriptions',
                'conversion_rates',
            ]);
    }

    /** @test */
    public function can_get_conversions_by_source()
    {
        $response = $this->actingAs($this->user)->getJson('/api/conversions/by-source');
        
        $response->assertOk();
    }

    /** @test */
    public function can_get_user_journey()
    {
        ConversionMetric::factory()->create(['user_id' => $this->user->id]);
        
        $response = $this->actingAs($this->user)->getJson('/api/conversions/user-journey');
        
        $response->assertOk();
    }

    /** @test */
    public function can_get_cohort_analysis()
    {
        $response = $this->actingAs($this->user)->getJson('/api/conversions/cohort-analysis');
        
        $response->assertOk();
    }

    /** @test */
    public function analytics_requires_authentication()
    {
        $response = $this->getJson('/api/analytics/user');
        $response->assertUnauthorized();
    }

    /** @test */
    public function analytics_event_validation_works()
    {
        $response = $this->actingAs($this->user)->postJson('/api/analytics/track', [
            'event_type' => 'invalid_event',
        ]);
        
        $response->assertUnprocessable();
    }
}
