<?php

namespace Tests\Feature\Frontend;

use App\Models\Post;
use App\Models\Series;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeriesFeaturesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test series index page displays all series with thumbnails and reading time.
     */
    public function test_series_index_page_displays_series_with_details(): void
    {
        // Create series with posts
        $series = Series::factory()->create([
            'name' => 'Laravel Mastery',
            'description' => 'Learn Laravel from basics to advanced',
        ]);

        $posts = Post::factory()->count(3)->create([
            'status' => 'published',
            'published_at' => now(),
            'reading_time' => 10,
        ]);

        foreach ($posts as $index => $post) {
            $post->series()->associate($series);
            $post->order_in_series = $index;
            $post->save();
        }

        $response = $this->get(route('series.index'));

        $response->assertStatus(200);
        $response->assertSee('Laravel Mastery');
        $response->assertSee('Learn Laravel from basics to advanced');
        $response->assertSee('3 articles');
        $response->assertSee('30 min total');
    }

    /**
     * Test series detail page shows all articles with progress indicators.
     */
    public function test_series_detail_page_shows_articles_with_progress(): void
    {
        $series = Series::factory()->create([
            'name' => 'Vue.js Fundamentals',
        ]);

        $posts = Post::factory()->count(5)->create([
            'status' => 'published',
            'published_at' => now(),
            'reading_time' => 8,
        ]);

        foreach ($posts as $index => $post) {
            $post->series()->associate($series);
            $post->order_in_series = $index;
            $post->save();
        }

        $response = $this->get(route('series.show', $series->slug));

        $response->assertStatus(200);
        $response->assertSee('Vue.js Fundamentals');
        $response->assertSee('5 articles');
        $response->assertSee('40 min total reading time');
        $response->assertSee('Your Progress');
        
        // Check that all posts are displayed
        foreach ($posts as $post) {
            $response->assertSee($post->title);
        }
    }

    /**
     * Test series detail page shows completion percentage.
     */
    public function test_series_detail_page_shows_completion_percentage(): void
    {
        $series = Series::factory()->create();
        
        $posts = Post::factory()->count(4)->create([
            'status' => 'published',
            'published_at' => now(),
        ]);

        foreach ($posts as $index => $post) {
            $post->series()->associate($series);
            $post->order_in_series = $index;
            $post->save();
        }

        $response = $this->get(route('series.show', $series->slug));

        $response->assertStatus(200);
        $response->assertSee('completionPercentage');
        $response->assertSee('readPosts');
    }

    /**
     * Test series index page handles empty series.
     */
    public function test_series_index_handles_no_series(): void
    {
        $response = $this->get(route('series.index'));

        $response->assertStatus(200);
        $response->assertSee('No series available yet');
    }

    /**
     * Test series detail page handles series with no posts.
     */
    public function test_series_detail_handles_no_posts(): void
    {
        $series = Series::factory()->create();

        $response = $this->get(route('series.show', $series->slug));

        $response->assertStatus(200);
        $response->assertSee('No posts in this series yet');
    }

    /**
     * Test series index pagination works correctly.
     */
    public function test_series_index_pagination_works(): void
    {
        Series::factory()->count(20)->create();

        $response = $this->get(route('series.index'));

        $response->assertStatus(200);
        // Should show pagination navigation (next/previous links)
        $this->assertTrue(
            str_contains($response->content(), 'Next') || 
            str_contains($response->content(), 'Previous') ||
            str_contains($response->content(), 'page=2')
        );
    }

    /**
     * Test series shows only published posts.
     */
    public function test_series_shows_only_published_posts(): void
    {
        $series = Series::factory()->create();

        $publishedPost = Post::factory()->create([
            'status' => 'published',
            'published_at' => now(),
            'title' => 'Published Post',
        ]);

        $draftPost = Post::factory()->create([
            'status' => 'draft',
            'title' => 'Draft Post',
        ]);

        $publishedPost->series()->associate($series);
        $publishedPost->order_in_series = 0;
        $publishedPost->save();
        $draftPost->series()->associate($series);
        $draftPost->order_in_series = 1;
        $draftPost->save();

        $response = $this->get(route('series.show', $series->slug));

        $response->assertStatus(200);
        $response->assertSee('Published Post');
        $response->assertDontSee('Draft Post');
    }

    /**
     * Test series total reading time calculation.
     */
    public function test_series_calculates_total_reading_time(): void
    {
        $series = Series::factory()->create();
        
        $posts = Post::factory()->count(4)->create([
            'status' => 'published',
            'published_at' => now(),
            'reading_time' => 15,
        ]);

        foreach ($posts as $index => $post) {
            $post->series()->associate($series);
            $post->order_in_series = $index;
            $post->save();
        }

        $response = $this->get(route('series.index'));

        $response->assertStatus(200);
        $response->assertSee('60 min total'); // 4 posts * 15 min
    }
}
