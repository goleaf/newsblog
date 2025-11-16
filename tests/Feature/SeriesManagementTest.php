<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Series;
use App\Models\User;
use App\Services\SeriesNavigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeriesManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_can_create_series(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('admin.series.store'), [
            'name' => 'Laravel Tutorial Series',
            'description' => 'A comprehensive guide to Laravel',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('series', [
            'name' => 'Laravel Tutorial Series',
            'slug' => 'laravel-tutorial-series',
        ]);
    }

    public function test_can_add_posts_to_series(): void
    {
        $series = Series::factory()->create();
        $post = Post::factory()->create([
            'user_id' => User::factory()->create()->id,
            'category_id' => Category::factory()->create()->id,
            'status' => 'published',
        ]);

        $post->series()->associate($series);
        $post->order_in_series = 0;
        $post->save();

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'series_id' => $series->id,
            'order_in_series' => 0,
        ]);
    }

    public function test_series_navigation_service_returns_correct_navigation(): void
    {
        $series = Series::factory()->create();
        $category = Category::factory()->create();
        $user = User::factory()->create();

        $post1 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'title' => 'Part 1',
        ]);
        $post2 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'title' => 'Part 2',
        ]);
        $post3 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'title' => 'Part 3',
        ]);

        $post1->series()->associate($series);
        $post1->order_in_series = 0;
        $post1->save();
        $post2->series()->associate($series);
        $post2->order_in_series = 1;
        $post2->save();
        $post3->series()->associate($series);
        $post3->order_in_series = 2;
        $post3->save();

        $service = app(SeriesNavigationService::class);
        $navigation = $service->getNavigation($post2, $series);

        $this->assertEquals($post1->id, $navigation['previous']->id);
        $this->assertEquals($post3->id, $navigation['next']->id);
        $this->assertEquals(2, $navigation['current_position']);
        $this->assertEquals(3, $navigation['total_posts']);
    }

    public function test_first_post_in_series_has_no_previous(): void
    {
        $series = Series::factory()->create();
        $category = Category::factory()->create();
        $user = User::factory()->create();

        $post1 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
        ]);
        $post2 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        $post1->series()->associate($series);
        $post1->order_in_series = 0;
        $post1->save();
        $post2->series()->associate($series);
        $post2->order_in_series = 1;
        $post2->save();

        $service = app(SeriesNavigationService::class);
        $navigation = $service->getNavigation($post1, $series);

        $this->assertNull($navigation['previous']);
        $this->assertEquals($post2->id, $navigation['next']->id);
    }

    public function test_last_post_in_series_has_no_next(): void
    {
        $series = Series::factory()->create();
        $category = Category::factory()->create();
        $user = User::factory()->create();

        $post1 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
        ]);
        $post2 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
        ]);

        $post1->series()->associate($series);
        $post1->order_in_series = 0;
        $post1->save();
        $post2->series()->associate($series);
        $post2->order_in_series = 1;
        $post2->save();

        $service = app(SeriesNavigationService::class);
        $navigation = $service->getNavigation($post2, $series);

        $this->assertEquals($post1->id, $navigation['previous']->id);
        $this->assertNull($navigation['next']);
    }

    public function test_can_view_series_index_page(): void
    {
        $series1 = Series::factory()->create(['name' => 'Series 1']);
        $series2 = Series::factory()->create(['name' => 'Series 2']);

        $response = $this->get(route('series.index'));

        $response->assertOk();
        $response->assertViewHas('series');
    }

    public function test_can_view_series_show_page(): void
    {
        $series = Series::factory()->create(['name' => 'Test Series']);
        $category = Category::factory()->create();
        $user = User::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'title' => 'Test Post',
        ]);

        $post->series()->associate($series);
        $post->order_in_series = 0;
        $post->save();

        $response = $this->get(route('series.show', $series->slug));

        $response->assertOk();
        $response->assertViewHas('series');
    }

    public function test_admin_can_access_series_management(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.series.index'));

        $response->assertOk();
    }

    public function test_non_admin_cannot_access_series_management(): void
    {
        $user = User::factory()->create(['role' => 'author']);
        $this->actingAs($user);

        $response = $this->get(route('admin.series.index'));

        $response->assertForbidden();
    }
}
