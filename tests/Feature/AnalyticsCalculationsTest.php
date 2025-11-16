<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\PostView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsCalculationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_traffic_sources_are_calculated(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        // Minimal category and user for FK constraints
        $categoryId = \DB::table('categories')->insertGetId([
            'name' => 'General',
            'slug' => 'general',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $authorId = User::factory()->create()->id;

        // Minimal posts using schema from migration
        $postIdA = \DB::table('posts')->insertGetId([
            'user_id' => $authorId,
            'category_id' => $categoryId,
            'title' => 'Post A',
            'slug' => 'post-a',
            'excerpt' => null,
            'content' => 'A',
            'status' => 'published',
            'is_featured' => false,
            'is_trending' => false,
            'view_count' => 0,
            'published_at' => now(),
            'reading_time' => 1,
            'meta_title' => null,
            'meta_description' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $postIdB = \DB::table('posts')->insertGetId([
            'user_id' => $authorId,
            'category_id' => $categoryId,
            'title' => 'Post B',
            'slug' => 'post-b',
            'excerpt' => null,
            'content' => 'B',
            'status' => 'published',
            'is_featured' => false,
            'is_trending' => false,
            'view_count' => 0,
            'published_at' => now(),
            'reading_time' => 1,
            'meta_title' => null,
            'meta_description' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Seed views with referers
        PostView::factory()->createMany([
            ['post_id' => $postIdA, 'session_id' => 's1', 'referer' => null, 'viewed_at' => now()],
            ['post_id' => $postIdA, 'session_id' => 's2', 'referer' => 'https://www.google.com?q=ai', 'viewed_at' => now()],
            ['post_id' => $postIdB, 'session_id' => 's3', 'referer' => 'https://twitter.com/some', 'viewed_at' => now()],
            ['post_id' => $postIdB, 'session_id' => 's4', 'referer' => 'https://ref.example.com/page', 'viewed_at' => now()],
            ['post_id' => $postIdB, 'session_id' => 's5', 'referer' => 'https://www.bing.com', 'viewed_at' => now()],
        ]);

        $response = $this->actingAs($admin)->get(route('admin.analytics', ['period' => 'week']));
        $response->assertOk();

        $response->assertViewHas('trafficSources', function ($sources) {
            // Expect keys
            return isset($sources['direct'], $sources['search'], $sources['social'], $sources['referral'])
                && $sources['direct'] >= 1
                && $sources['search'] >= 1
                && $sources['social'] >= 1
                && $sources['referral'] >= 1;
        });
    }

    public function test_views_over_time_contains_dates_and_counts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        // Minimal category and user for FK constraints
        $categoryId = \DB::table('categories')->insertGetId([
            'name' => 'General',
            'slug' => 'general',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $authorId = User::factory()->create()->id;

        $postId = \DB::table('posts')->insertGetId([
            'user_id' => $authorId,
            'category_id' => $categoryId,
            'title' => 'Post C',
            'slug' => 'post-c',
            'excerpt' => null,
            'content' => 'C',
            'status' => 'published',
            'is_featured' => false,
            'is_trending' => false,
            'view_count' => 0,
            'published_at' => now(),
            'reading_time' => 1,
            'meta_title' => null,
            'meta_description' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        PostView::factory()->createMany([
            ['post_id' => $postId, 'session_id' => 'sa', 'referer' => null, 'viewed_at' => now()->subDays(1)],
            ['post_id' => $postId, 'session_id' => 'sb', 'referer' => null, 'viewed_at' => now()],
        ]);

        $response = $this->actingAs($admin)->get(route('admin.analytics', ['period' => 'month']));
        $response->assertOk()
            ->assertViewHas('viewStats', function ($stats) {
                return isset($stats['views_over_time']) && $stats['views_over_time']->count() >= 1;
            });
    }
}
