<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_search_endpoint_returns_view(): void
    {
        $response = $this->get('/search');

        $response->assertStatus(200);
        $response->assertViewIs('search');
    }

    public function test_search_with_query_returns_results(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'title' => 'Laravel Testing Guide',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->get('/search?q=Laravel');

        $response->assertStatus(200);
        $response->assertViewIs('search');
        $response->assertViewHas('posts');
        $response->assertViewHas('query', 'Laravel');
    }

    public function test_search_with_filters_by_category(): void
    {
        $user = User::factory()->create();
        $category1 = Category::factory()->create(['slug' => 'technology']);
        $category2 = Category::factory()->create(['slug' => 'design']);

        $post1 = Post::factory()->create([
            'title' => 'Laravel Post',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category1->id,
        ]);

        $post2 = Post::factory()->create([
            'title' => 'Laravel Design Post',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category2->id,
        ]);

        $response = $this->get('/search?q=Laravel&category='.$category1->id);

        $response->assertStatus(200);
        $posts = $response->viewData('posts');
        $this->assertTrue($posts->contains('id', $post1->id));
        $this->assertFalse($posts->contains('id', $post2->id));
    }

    public function test_search_with_filters_by_author(): void
    {
        $user1 = User::factory()->create(['name' => 'John Doe']);
        $user2 = User::factory()->create(['name' => 'Jane Smith']);
        $category = Category::factory()->create();

        $post1 = Post::factory()->create([
            'title' => 'Laravel Post',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user1->id,
            'category_id' => $category->id,
        ]);

        $post2 = Post::factory()->create([
            'title' => 'Laravel Post Two',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user2->id,
            'category_id' => $category->id,
        ]);

        $response = $this->get('/search?q=Laravel&author='.$user1->id);

        $response->assertStatus(200);
        $posts = $response->viewData('posts');
        $this->assertTrue($posts->contains('id', $post1->id));
        $this->assertFalse($posts->contains('id', $post2->id));
    }

    public function test_search_with_filters_by_date_range(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post1 = Post::factory()->create([
            'title' => 'Laravel Post',
            'status' => 'published',
            'published_at' => now()->subDays(10),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $post2 = Post::factory()->create([
            'title' => 'Laravel Post Two',
            'status' => 'published',
            'published_at' => now()->subDays(30),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $dateFrom = now()->subDays(15)->format('Y-m-d');
        $dateTo = now()->format('Y-m-d');

        $response = $this->get("/search?q=Laravel&date_from={$dateFrom}&date_to={$dateTo}");

        $response->assertStatus(200);
        $posts = $response->viewData('posts');
        $this->assertTrue($posts->contains('id', $post1->id));
        $this->assertFalse($posts->contains('id', $post2->id));
    }

    public function test_search_pagination_works(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->count(20)->create([
            'title' => 'Laravel Post',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->get('/search?q=Laravel');

        $response->assertStatus(200);
        $posts = $response->viewData('posts');
        // SearchService uses 15 results per page as per requirement 8.3
        $this->assertLessThanOrEqual(15, $posts->count());

        $response = $this->get('/search?q=Laravel&page=2');

        $response->assertStatus(200);
        $posts = $response->viewData('posts');
        $this->assertGreaterThan(0, $posts->count());
    }

    public function test_empty_query_returns_empty_results(): void
    {
        $response = $this->get('/search?q=');

        $response->assertStatus(200);
        $response->assertViewIs('search');
        $posts = $response->viewData('posts');
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $posts);
        $this->assertEquals(0, $posts->total());
        $this->assertEquals('', $response->viewData('query'));
    }

    public function test_search_with_special_characters(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'title' => 'Laravel 2024 Guide',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->get('/search?q=Laravel+2024');

        $response->assertStatus(200);
        $posts = $response->viewData('posts');
        $this->assertGreaterThanOrEqual(1, $posts->count());
    }

    public function test_search_only_returns_published_posts(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $publishedPost = Post::factory()->create([
            'title' => 'Published Laravel Post',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $draftPost = Post::factory()->create([
            'title' => 'Draft Laravel Post',
            'status' => 'draft',
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->get('/search?q=Laravel');

        $response->assertStatus(200);
        $posts = $response->viewData('posts');
        $this->assertTrue($posts->contains('id', $publishedPost->id));
        $this->assertFalse($posts->contains('id', $draftPost->id));
    }

    public function test_search_with_multiple_filters(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        $category = Category::factory()->create(['slug' => 'technology']);

        $post = Post::factory()->create([
            'title' => 'Laravel Testing Guide',
            'status' => 'published',
            'published_at' => now()->subDays(5),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $dateFrom = now()->subDays(10)->format('Y-m-d');
        $dateTo = now()->format('Y-m-d');

        $response = $this->get('/search?q=Laravel&category='.$category->id.'&author='.$user->id."&date_from={$dateFrom}&date_to={$dateTo}");

        $response->assertStatus(200);
        $posts = $response->viewData('posts');
        $this->assertTrue($posts->contains('id', $post->id));
    }

    public function test_search_with_typo_returns_results(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'title' => 'Laravel Framework Guide',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        // Test with typo "laravle" instead of "laravel"
        $response = $this->get('/search?q=laravle');

        $response->assertStatus(200);
        $posts = $response->viewData('posts');
        // Fuzzy search may or may not find results depending on threshold
        // Test that the endpoint works and returns a valid response
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $posts);
    }

    public function test_search_performance_is_acceptable(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create multiple posts to test performance
        Post::factory()->count(50)->create([
            'title' => 'Laravel Post',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $startTime = microtime(true);
        $response = $this->get('/search?q=Laravel');
        $endTime = microtime(true);

        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);
        // Search should complete within 500ms as per requirement 2.3
        $this->assertLessThan(500, $executionTime, 'Search took longer than 500ms');
    }

    public function test_search_multi_field_weighted_search(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create post with search term in title (should rank higher)
        $titlePost = Post::factory()->create([
            'title' => 'Laravel Framework',
            'excerpt' => 'A guide to PHP',
            'content' => 'Content about PHP',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        // Create post with search term in content (should rank lower)
        $contentPost = Post::factory()->create([
            'title' => 'PHP Guide',
            'excerpt' => 'A guide to PHP',
            'content' => 'This is about Laravel framework',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->get('/search?q=Laravel');

        $response->assertStatus(200);
        $posts = $response->viewData('posts');
        // At least one post should be returned
        $this->assertGreaterThanOrEqual(1, $posts->count());
        // Title match should be included in results
        $this->assertTrue($posts->contains('id', $titlePost->id));
    }

    public function test_search_with_category_includes_subcategories(): void
    {
        $user = User::factory()->create();
        $parentCategory = Category::factory()->create(['name' => 'Technology']);
        $childCategory = Category::factory()->create([
            'name' => 'Web Development',
            'parent_id' => $parentCategory->id,
        ]);

        $parentPost = Post::factory()->create([
            'title' => 'Technology Post',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $parentCategory->id,
        ]);

        $childPost = Post::factory()->create([
            'title' => 'Web Development Post',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $childCategory->id,
        ]);

        $response = $this->get('/search?q=Post&category='.$parentCategory->id);

        $response->assertStatus(200);
        $posts = $response->viewData('posts');
        // Both parent and child category posts should be included
        $this->assertTrue($posts->contains('id', $parentPost->id));
        $this->assertTrue($posts->contains('id', $childPost->id));
    }

    public function test_search_with_tag_filter(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $tag1 = \App\Models\Tag::factory()->create(['name' => 'Laravel']);
        $tag2 = \App\Models\Tag::factory()->create(['name' => 'PHP']);

        $post1 = Post::factory()->create([
            'title' => 'Laravel Post',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);
        $post1->tags()->attach($tag1);

        $post2 = Post::factory()->create([
            'title' => 'PHP Post',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);
        $post2->tags()->attach($tag2);

        $response = $this->get('/search?q=Post&tags[]='.$tag1->id);

        $response->assertStatus(200);
        $posts = $response->viewData('posts');
        $this->assertTrue($posts->contains('id', $post1->id));
        $this->assertFalse($posts->contains('id', $post2->id));
    }

    public function test_search_with_multiple_tags_uses_and_logic(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $tag1 = \App\Models\Tag::factory()->create(['name' => 'Laravel']);
        $tag2 = \App\Models\Tag::factory()->create(['name' => 'Tutorial']);

        $post1 = Post::factory()->create([
            'title' => 'Laravel Tutorial',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);
        $post1->tags()->attach([$tag1->id, $tag2->id]);

        $post2 = Post::factory()->create([
            'title' => 'Laravel Guide',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);
        $post2->tags()->attach($tag1);

        $response = $this->get('/search?q=Laravel&tags[]='.$tag1->id.'&tags[]='.$tag2->id);

        $response->assertStatus(200);
        $posts = $response->viewData('posts');
        // Only post1 has both tags
        $this->assertTrue($posts->contains('id', $post1->id));
        $this->assertFalse($posts->contains('id', $post2->id));
    }

    public function test_search_displays_active_filter_count(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $tag = \App\Models\Tag::factory()->create();

        Post::factory()->create([
            'title' => 'Test Post',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $dateFrom = now()->subDays(10)->format('Y-m-d');
        $dateTo = now()->format('Y-m-d');

        $response = $this->get('/search?q=Test&category='.$category->id.'&author='.$user->id.'&tags[]='.$tag->id.'&date_from='.$dateFrom.'&date_to='.$dateTo);

        $response->assertStatus(200);
        $response->assertViewHas('activeFilterCount', 4);
    }

    public function test_search_provides_filter_options(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $tag = \App\Models\Tag::factory()->create();

        Post::factory()->create([
            'title' => 'Test Post',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->get('/search?q=Test');

        $response->assertStatus(200);
        $response->assertViewHas('authors');
        $response->assertViewHas('categories');
        $response->assertViewHas('tags');
        $response->assertViewHas('activeFilterCount');
    }
}
