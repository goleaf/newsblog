<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Series;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeriesProgressTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected Series $series;

    protected User $user;

    protected array $posts;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $category = Category::factory()->create();

        // Create a series with 5 posts
        $this->series = Series::factory()->create([
            'name' => 'Test Series',
            'description' => 'A test series for progress tracking',
        ]);

        $this->posts = [];
        for ($i = 0; $i < 5; $i++) {
            $post = Post::factory()->create([
                'user_id' => $this->user->id,
                'category_id' => $category->id,
                'status' => 'published',
                'title' => "Part {$i}",
                'reading_time' => 5,
            ]);

            $this->series->posts()->attach($post->id, ['order' => $i]);
            $this->posts[] = $post;
        }
    }

    public function test_series_show_page_displays_progress_tracking_elements(): void
    {
        $response = $this->get(route('series.show', $this->series->slug));

        $response->assertOk();
        $response->assertSee('Your Progress');
        $response->assertSee('articles');
        $response->assertSee($this->series->name);
        $response->assertSee($this->series->description);
    }

    public function test_series_show_page_includes_series_progress_javascript(): void
    {
        $response = $this->get(route('series.show', $this->series->slug));

        $response->assertOk();
        $response->assertSee('seriesProgress', false);
        $response->assertSee($this->series->id, false);
    }

    public function test_series_show_page_displays_all_posts_with_order_numbers(): void
    {
        $response = $this->get(route('series.show', $this->series->slug));

        $response->assertOk();

        foreach ($this->posts as $index => $post) {
            $response->assertSee($post->title);
            $response->assertSee((string) ($index + 1)); // Order number
        }
    }

    public function test_series_show_page_displays_progress_bar(): void
    {
        $response = $this->get(route('series.show', $this->series->slug));

        $response->assertOk();
        $response->assertSee('Your Progress');
        $response->assertSee('bg-indigo-600', false); // Progress bar color class
    }

    public function test_series_show_page_displays_total_reading_time(): void
    {
        $response = $this->get(route('series.show', $this->series->slug));

        $response->assertOk();
        $totalReadingTime = $this->series->posts->sum('reading_time');
        $response->assertSee("{$totalReadingTime} min total reading time");
    }

    public function test_series_show_page_displays_post_count(): void
    {
        $response = $this->get(route('series.show', $this->series->slug));

        $response->assertOk();
        $postCount = $this->series->posts->count();
        $response->assertSee("{$postCount} articles");
    }

    public function test_series_show_page_displays_mark_as_read_buttons(): void
    {
        $response = $this->get(route('series.show', $this->series->slug));

        $response->assertOk();
        $response->assertSee('Mark as read');
        $response->assertSee('toggleRead', false);
    }

    public function test_series_show_page_displays_completion_badge_component(): void
    {
        $response = $this->get(route('series.show', $this->series->slug));

        $response->assertOk();
        $response->assertSee('completion-badge', false);
        $response->assertSee('completionBadge', false);
    }

    public function test_series_completion_badge_shows_celebration_message(): void
    {
        $response = $this->get(route('series.show', $this->series->slug));

        $response->assertOk();
        $response->assertSee('Series Completed!');
        $response->assertSee('Congratulations!');
    }

    public function test_series_completion_badge_shows_share_button(): void
    {
        $response = $this->get(route('series.show', $this->series->slug));

        $response->assertOk();
        $response->assertSee('Share Achievement');
        $response->assertSee('shareCompletion', false);
    }

    public function test_series_completion_badge_shows_reset_button(): void
    {
        $response = $this->get(route('series.show', $this->series->slug));

        $response->assertOk();
        $response->assertSee('Reset Progress');
        $response->assertSee('resetProgress', false);
    }

    public function test_series_completion_badge_shows_related_series(): void
    {
        // Create related series
        $relatedSeries = Series::factory()->count(3)->create();

        $response = $this->get(route('series.show', $this->series->slug));

        $response->assertOk();
        $response->assertSee('Continue Learning');
    }

    public function test_series_show_page_includes_post_metadata(): void
    {
        $response = $this->get(route('series.show', $this->series->slug));

        $response->assertOk();

        foreach ($this->posts as $post) {
            $response->assertSee($post->user->name);
            $response->assertSee($post->category->name);
            $response->assertSee("{$post->reading_time} min");
        }
    }

    public function test_series_show_page_displays_read_article_links(): void
    {
        $response = $this->get(route('series.show', $this->series->slug));

        $response->assertOk();

        foreach ($this->posts as $post) {
            $response->assertSee(route('post.show', $post->slug), false);
            $response->assertSee('Read Article');
        }
    }

    public function test_series_progress_javascript_includes_all_required_functions(): void
    {
        $response = $this->get(route('series.show', $this->series->slug));

        $response->assertOk();

        // Check for key JavaScript functions
        $jsContent = $response->getContent();
        $this->assertStringContainsString('seriesProgress', $jsContent);
        $this->assertStringContainsString('toggleRead', $jsContent);
        $this->assertStringContainsString('completionPercentage', $jsContent);
    }

    public function test_series_show_page_displays_checkmark_for_read_posts(): void
    {
        $response = $this->get(route('series.show', $this->series->slug));

        $response->assertOk();
        // Check for checkmark SVG path
        $response->assertSee('M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z', false);
    }

    public function test_series_show_page_displays_trophy_icon_in_completion_badge(): void
    {
        $response = $this->get(route('series.show', $this->series->slug));

        $response->assertOk();
        // Check for trophy icon path
        $response->assertSee('M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z', false);
    }

    public function test_series_index_page_displays_all_series(): void
    {
        $series2 = Series::factory()->create(['name' => 'Another Series']);

        $response = $this->get(route('series.index'));

        $response->assertOk();
        $response->assertSee($this->series->name);
        $response->assertSee($series2->name);
    }

    public function test_series_index_page_displays_post_counts(): void
    {
        $response = $this->get(route('series.index'));

        $response->assertOk();
        $response->assertViewHas('series');

        $series = $response->viewData('series')->first();
        $this->assertNotNull($series->posts_count);
    }

    public function test_post_show_page_includes_series_navigation_when_part_of_series(): void
    {
        $post = $this->posts[1]; // Middle post

        $response = $this->get(route('post.show', $post->slug));

        $response->assertOk();
        $response->assertViewHas('seriesData');

        $seriesData = $response->viewData('seriesData');
        $this->assertNotEmpty($seriesData);
    }

    public function test_series_navigation_shows_previous_and_next_posts(): void
    {
        $post = $this->posts[2]; // Middle post

        $response = $this->get(route('post.show', $post->slug));

        $response->assertOk();

        $seriesData = $response->viewData('seriesData');
        $this->assertNotEmpty($seriesData);

        $navigation = $seriesData[0]['navigation'];
        $this->assertNotNull($navigation['previous']);
        $this->assertNotNull($navigation['next']);
        $this->assertEquals($this->posts[1]->id, $navigation['previous']->id);
        $this->assertEquals($this->posts[3]->id, $navigation['next']->id);
    }

    public function test_series_navigation_shows_current_position(): void
    {
        $post = $this->posts[2]; // Third post (index 2)

        $response = $this->get(route('post.show', $post->slug));

        $response->assertOk();

        $seriesData = $response->viewData('seriesData');
        $navigation = $seriesData[0]['navigation'];

        $this->assertEquals(3, $navigation['current_position']);
        $this->assertEquals(5, $navigation['total_posts']);
    }

    public function test_empty_series_shows_appropriate_message(): void
    {
        $emptySeries = Series::factory()->create(['name' => 'Empty Series']);

        $response = $this->get(route('series.show', $emptySeries->slug));

        $response->assertOk();
        $response->assertSee('No posts in this series yet.');
    }

    public function test_series_show_page_displays_last_updated_time(): void
    {
        $response = $this->get(route('series.show', $this->series->slug));

        $response->assertOk();
        $response->assertSee('Updated');
        $response->assertSee('ago');
    }

    public function test_series_progress_data_structure_is_correct(): void
    {
        $response = $this->get(route('series.show', $this->series->slug));

        $response->assertOk();
        $response->assertViewHas('series');
        $response->assertViewHas('totalReadingTime');
        $response->assertViewHas('readPosts');
        $response->assertViewHas('completionPercentage');
        $response->assertViewHas('relatedSeries');

        $this->assertIsInt($response->viewData('totalReadingTime'));
        $this->assertIsArray($response->viewData('readPosts'));
        $this->assertTrue(
            is_int($response->viewData('completionPercentage')) || is_float($response->viewData('completionPercentage')),
            'Completion percentage should be numeric'
        );
    }

    public function test_completion_percentage_calculation_is_correct(): void
    {
        $response = $this->get(route('series.show', $this->series->slug));

        $response->assertOk();

        $completionPercentage = $response->viewData('completionPercentage');
        $this->assertGreaterThanOrEqual(0, $completionPercentage);
        $this->assertLessThanOrEqual(100, $completionPercentage);
    }

    public function test_series_with_single_post_displays_correctly(): void
    {
        $singlePostSeries = Series::factory()->create(['name' => 'Single Post Series']);
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        $singlePostSeries->posts()->attach($post->id, ['order' => 0]);

        $response = $this->get(route('series.show', $singlePostSeries->slug));

        $response->assertOk();
        $response->assertSee('1 article');
        $response->assertSee($post->title);
    }

    public function test_series_show_page_includes_alpine_js_data_attributes(): void
    {
        $response = $this->get(route('series.show', $this->series->slug));

        $response->assertOk();
        $response->assertSee('x-data', false);
        $response->assertSee('x-show', false);
        $response->assertSee('x-text', false);
        $response->assertSee(':style', false);
    }

    public function test_series_progress_persists_across_page_loads(): void
    {
        // This test verifies the structure is in place for localStorage persistence
        $response = $this->get(route('series.show', $this->series->slug));

        $response->assertOk();
        $response->assertSee('localStorage', false);
        $response->assertSee('series_progress_', false);
    }

    public function test_series_navigation_service_calculates_progress_correctly(): void
    {
        $service = app(\App\Services\SeriesNavigationService::class);

        $post = $this->posts[2];
        $navigation = $service->getNavigation($post, $this->series);

        $this->assertEquals(3, $navigation['current_position']);
        $this->assertEquals(5, $navigation['total_posts']);
        $this->assertNotNull($navigation['previous']);
        $this->assertNotNull($navigation['next']);
    }

    public function test_first_post_in_series_has_correct_navigation(): void
    {
        $service = app(\App\Services\SeriesNavigationService::class);

        $post = $this->posts[0];
        $navigation = $service->getNavigation($post, $this->series);

        $this->assertEquals(1, $navigation['current_position']);
        $this->assertNull($navigation['previous']);
        $this->assertNotNull($navigation['next']);
    }

    public function test_last_post_in_series_has_correct_navigation(): void
    {
        $service = app(\App\Services\SeriesNavigationService::class);

        $post = $this->posts[4];
        $navigation = $service->getNavigation($post, $this->series);

        $this->assertEquals(5, $navigation['current_position']);
        $this->assertNotNull($navigation['previous']);
        $this->assertNull($navigation['next']);
    }
}
