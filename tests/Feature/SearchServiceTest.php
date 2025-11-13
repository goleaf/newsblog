<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SearchService $searchService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->searchService = app(SearchService::class);
    }

    public function test_searches_posts_by_title(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Laravel Framework Guide',
            'status' => 'published',
            'published_at' => now(),
        ]);

        Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'JavaScript Tutorial',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $results = $this->searchService->search('Laravel');

        $this->assertCount(1, $results);
        $this->assertStringContainsString('Laravel', $results->first()->title);
    }

    public function test_searches_posts_by_content(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Web Development',
            'content' => 'This post discusses Laravel framework in detail',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $results = $this->searchService->search('Laravel');

        $this->assertCount(1, $results);
    }

    public function test_highlights_matching_terms(): void
    {
        $text = 'Laravel is a great framework';
        $highlighted = $this->searchService->highlightMatches($text, 'Laravel');

        $this->assertStringContainsString('<mark class="search-highlight">Laravel</mark>', $highlighted);
    }

    public function test_filters_by_category(): void
    {
        $user = User::factory()->create();
        $category1 = Category::factory()->create(['slug' => 'php']);
        $category2 = Category::factory()->create(['slug' => 'javascript']);

        Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category1->id,
            'title' => 'PHP Tutorial',
            'status' => 'published',
            'published_at' => now(),
        ]);

        Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category2->id,
            'title' => 'JS Tutorial',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $results = $this->searchService->search('Tutorial', ['category' => 'php']);

        $this->assertCount(1, $results);
        $this->assertEquals($category1->id, $results->first()->category_id);
    }

    public function test_returns_search_suggestions(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Laravel Best Practices',
            'status' => 'published',
            'published_at' => now(),
        ]);

        Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Laravel Tutorial',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $suggestions = $this->searchService->getSuggestions('Lar', 5);

        $this->assertCount(2, $suggestions);
    }

    public function test_only_searches_published_posts(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Published Laravel Post',
            'status' => 'published',
            'published_at' => now(),
        ]);

        Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Draft Laravel Post',
            'status' => 'draft',
        ]);

        $results = $this->searchService->search('Laravel');

        $this->assertCount(1, $results);
        $this->assertEquals('published', $results->first()->status);
    }
}
