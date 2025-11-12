<?php

namespace Tests\Feature\Nova;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Nova\Filters\DateRange;
use App\Nova\Filters\PostAuthor;
use App\Nova\Filters\PostCategory;
use App\Nova\Filters\PostFeatured;
use App\Nova\Filters\PostStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Nova\Http\Requests\NovaRequest;
use Tests\TestCase;

class PostFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_status_filter_filters_by_draft(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Post::factory()->create(['status' => 'draft']);
        Post::factory()->create(['status' => 'published']);
        Post::factory()->create(['status' => 'scheduled']);

        $request = NovaRequest::create('/nova-api/posts', 'GET');
        $request->setUserResolver(fn () => $admin);

        $filter = new PostStatus;
        $query = $filter->apply($request, Post::query(), 'draft');

        $this->assertEquals(1, $query->count());
        $this->assertEquals('draft', $query->first()->status);
    }

    public function test_post_status_filter_filters_by_published(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Post::factory()->create(['status' => 'draft']);
        Post::factory()->create(['status' => 'published']);
        Post::factory()->create(['status' => 'published']);

        $request = NovaRequest::create('/nova-api/posts', 'GET');
        $request->setUserResolver(fn () => $admin);

        $filter = new PostStatus;
        $query = $filter->apply($request, Post::query(), 'published');

        $this->assertEquals(2, $query->count());
    }

    public function test_post_status_filter_has_correct_options(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $request = NovaRequest::create('/nova-api/posts', 'GET');
        $request->setUserResolver(fn () => $admin);

        $filter = new PostStatus;
        $options = $filter->options($request);

        $this->assertArrayHasKey('Draft', $options);
        $this->assertArrayHasKey('Published', $options);
        $this->assertArrayHasKey('Scheduled', $options);
        $this->assertEquals('draft', $options['Draft']);
        $this->assertEquals('published', $options['Published']);
        $this->assertEquals('scheduled', $options['Scheduled']);
    }

    public function test_post_category_filter_filters_by_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category1 = Category::factory()->create(['name' => 'Technology']);
        $category2 = Category::factory()->create(['name' => 'Science']);

        Post::factory()->create(['category_id' => $category1->id]);
        Post::factory()->create(['category_id' => $category1->id]);
        Post::factory()->create(['category_id' => $category2->id]);

        $request = NovaRequest::create('/nova-api/posts', 'GET');
        $request->setUserResolver(fn () => $admin);

        $filter = new PostCategory;
        $query = $filter->apply($request, Post::query(), $category1->id);

        $this->assertEquals(2, $query->count());
        $this->assertTrue($query->get()->every(fn ($post) => $post->category_id === $category1->id));
    }

    public function test_post_category_filter_has_categories_as_options(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Category::factory()->create(['name' => 'Technology', 'display_order' => 1]);
        Category::factory()->create(['name' => 'Science', 'display_order' => 2]);

        $request = NovaRequest::create('/nova-api/posts', 'GET');
        $request->setUserResolver(fn () => $admin);

        $filter = new PostCategory;
        $options = $filter->options($request);

        $this->assertCount(2, $options);
        $this->assertContains('Technology', $options);
        $this->assertContains('Science', $options);
    }

    public function test_post_author_filter_filters_by_author(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $author1 = User::factory()->create(['name' => 'John Doe']);
        $author2 = User::factory()->create(['name' => 'Jane Smith']);

        Post::factory()->create(['user_id' => $author1->id]);
        Post::factory()->create(['user_id' => $author1->id]);
        Post::factory()->create(['user_id' => $author2->id]);

        $request = NovaRequest::create('/nova-api/posts', 'GET');
        $request->setUserResolver(fn () => $admin);

        $filter = new PostAuthor;
        $query = $filter->apply($request, Post::query(), $author1->id);

        $this->assertEquals(2, $query->count());
        $this->assertTrue($query->get()->every(fn ($post) => $post->user_id === $author1->id));
    }

    public function test_post_author_filter_has_users_as_options(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['name' => 'John Doe']);
        User::factory()->create(['name' => 'Jane Smith']);

        $request = NovaRequest::create('/nova-api/posts', 'GET');
        $request->setUserResolver(fn () => $admin);

        $filter = new PostAuthor;
        $options = $filter->options($request);

        $this->assertGreaterThanOrEqual(3, count($options)); // At least admin + 2 authors
        $this->assertContains('John Doe', $options);
        $this->assertContains('Jane Smith', $options);
    }

    public function test_post_featured_filter_filters_featured_posts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Post::factory()->create(['is_featured' => true]);
        Post::factory()->create(['is_featured' => true]);
        Post::factory()->create(['is_featured' => false]);

        $request = NovaRequest::create('/nova-api/posts', 'GET');
        $request->setUserResolver(fn () => $admin);

        $filter = new PostFeatured;
        $query = $filter->apply($request, Post::query(), '1');

        $this->assertEquals(2, $query->count());
        $this->assertTrue($query->get()->every(fn ($post) => $post->is_featured === true));
    }

    public function test_post_featured_filter_filters_non_featured_posts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Post::factory()->create(['is_featured' => true]);
        Post::factory()->create(['is_featured' => false]);
        Post::factory()->create(['is_featured' => false]);

        $request = NovaRequest::create('/nova-api/posts', 'GET');
        $request->setUserResolver(fn () => $admin);

        $filter = new PostFeatured;
        $query = $filter->apply($request, Post::query(), '0');

        $this->assertEquals(2, $query->count());
        $this->assertTrue($query->get()->every(fn ($post) => $post->is_featured === false));
    }

    public function test_post_featured_filter_has_correct_options(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $request = NovaRequest::create('/nova-api/posts', 'GET');
        $request->setUserResolver(fn () => $admin);

        $filter = new PostFeatured;
        $options = $filter->options($request);

        $this->assertArrayHasKey('Featured', $options);
        $this->assertArrayHasKey('Not Featured', $options);
        $this->assertEquals('1', $options['Featured']);
        $this->assertEquals('0', $options['Not Featured']);
    }

    public function test_date_range_filter_filters_by_published_date(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $targetDate = now()->subDays(5)->startOfDay();
        Post::factory()->create(['published_at' => $targetDate]);
        Post::factory()->create(['published_at' => now()->subDays(10)]);
        Post::factory()->create(['published_at' => now()->subDays(1)]);

        $request = NovaRequest::create('/nova-api/posts', 'GET');
        $request->setUserResolver(fn () => $admin);

        $filter = new DateRange;
        $query = $filter->apply($request, Post::query(), $targetDate->toDateString());

        $this->assertEquals(1, $query->count());
        $this->assertEquals($targetDate->toDateString(), $query->first()->published_at->toDateString());
    }

    public function test_date_range_filter_returns_all_when_empty_value(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Post::factory()->create(['published_at' => now()->subDays(5)]);
        Post::factory()->create(['published_at' => now()->subDays(10)]);
        Post::factory()->create(['published_at' => now()->subDays(1)]);

        $request = NovaRequest::create('/nova-api/posts', 'GET');
        $request->setUserResolver(fn () => $admin);

        $filter = new DateRange;
        $query = $filter->apply($request, Post::query(), null);

        $this->assertEquals(3, $query->count());
    }
}
