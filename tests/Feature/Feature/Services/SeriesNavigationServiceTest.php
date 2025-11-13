<?php

namespace Tests\Feature\Feature\Services;

use App\Models\Post;
use App\Models\Series;
use App\Services\SeriesNavigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeriesNavigationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SeriesNavigationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SeriesNavigationService::class);
    }

    public function test_gets_navigation_for_first_post(): void
    {
        $series = Series::factory()->create();
        $post1 = Post::factory()->create();
        $post2 = Post::factory()->create();
        $post3 = Post::factory()->create();

        $series->posts()->attach($post1->id, ['order' => 1]);
        $series->posts()->attach($post2->id, ['order' => 2]);
        $series->posts()->attach($post3->id, ['order' => 3]);

        $navigation = $this->service->getNavigation($post1, $series);

        $this->assertNull($navigation['previous']);
        $this->assertEquals($post2->id, $navigation['next']->id);
        $this->assertEquals(1, $navigation['current_position']);
        $this->assertEquals(3, $navigation['total_posts']);
    }

    public function test_gets_navigation_for_middle_post(): void
    {
        $series = Series::factory()->create();
        $post1 = Post::factory()->create();
        $post2 = Post::factory()->create();
        $post3 = Post::factory()->create();

        $series->posts()->attach($post1->id, ['order' => 1]);
        $series->posts()->attach($post2->id, ['order' => 2]);
        $series->posts()->attach($post3->id, ['order' => 3]);

        $navigation = $this->service->getNavigation($post2, $series);

        $this->assertEquals($post1->id, $navigation['previous']->id);
        $this->assertEquals($post3->id, $navigation['next']->id);
        $this->assertEquals(2, $navigation['current_position']);
        $this->assertEquals(3, $navigation['total_posts']);
    }

    public function test_gets_navigation_for_last_post(): void
    {
        $series = Series::factory()->create();
        $post1 = Post::factory()->create();
        $post2 = Post::factory()->create();
        $post3 = Post::factory()->create();

        $series->posts()->attach($post1->id, ['order' => 1]);
        $series->posts()->attach($post2->id, ['order' => 2]);
        $series->posts()->attach($post3->id, ['order' => 3]);

        $navigation = $this->service->getNavigation($post3, $series);

        $this->assertEquals($post2->id, $navigation['previous']->id);
        $this->assertNull($navigation['next']);
        $this->assertEquals(3, $navigation['current_position']);
        $this->assertEquals(3, $navigation['total_posts']);
    }

    public function test_handles_post_not_in_series(): void
    {
        $series = Series::factory()->create();
        $post = Post::factory()->create();

        $navigation = $this->service->getNavigation($post, $series);

        $this->assertNull($navigation['previous']);
        $this->assertNull($navigation['next']);
        $this->assertEquals(0, $navigation['current_position']);
        $this->assertEquals(0, $navigation['total_posts']);
    }

    public function test_gets_all_series_with_navigation_for_post(): void
    {
        $series1 = Series::factory()->create();
        $series2 = Series::factory()->create();
        $post = Post::factory()->create();

        $series1->posts()->attach($post->id, ['order' => 1]);
        $series2->posts()->attach($post->id, ['order' => 2]);

        $seriesData = $this->service->getPostSeriesWithNavigation($post);

        $this->assertCount(2, $seriesData);
        $this->assertArrayHasKey('series', $seriesData[0]);
        $this->assertArrayHasKey('navigation', $seriesData[0]);
    }
}
