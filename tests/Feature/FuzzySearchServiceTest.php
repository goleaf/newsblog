<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\SearchClick;
use App\Models\SearchLog;
use App\Models\User;
use App\Services\FuzzySearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FuzzySearchServiceTest extends TestCase
{
    use RefreshDatabase;

    protected FuzzySearchService $searchService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->searchService = app(FuzzySearchService::class);
    }

    public function test_can_search_posts_with_exact_match(): void
    {
        $post = Post::factory()->create([
            'title' => 'Laravel Testing Guide',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $results = $this->searchService->search('Laravel Testing Guide');

        $this->assertNotEmpty($results);
        $this->assertEquals($post->id, $results[0]->id);
    }

    public function test_can_search_posts_with_fuzzy_match(): void
    {
        $post = Post::factory()->create([
            'title' => 'Laravel Testing Guide',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $results = $this->searchService->search('Laravl Testig Guid');

        $this->assertNotEmpty($results);
        $this->assertEquals($post->id, $results[0]->id);
    }

    public function test_search_respects_threshold(): void
    {
        Post::factory()->create([
            'title' => 'Laravel Testing Guide',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $resultsLowThreshold = $this->searchService->search('xyz', threshold: 20);
        $resultsHighThreshold = $this->searchService->search('xyz', threshold: 90);

        $this->assertGreaterThanOrEqual(count($resultsHighThreshold), count($resultsLowThreshold));
    }

    public function test_search_only_returns_published_posts(): void
    {
        Post::factory()->create([
            'title' => 'Published Post',
            'status' => 'published',
            'published_at' => now(),
        ]);

        Post::factory()->create([
            'title' => 'Draft Post',
            'status' => 'draft',
        ]);

        $results = $this->searchService->search('Post');

        $this->assertCount(1, $results);
        $this->assertEquals('Published Post', $results[0]->title);
    }

    public function test_search_can_filter_by_category(): void
    {
        $category1 = \App\Models\Category::factory()->create();
        $category2 = \App\Models\Category::factory()->create();

        $post1 = Post::factory()->create([
            'title' => 'Laravel Post',
            'status' => 'published',
            'published_at' => now(),
            'category_id' => $category1->id,
        ]);

        $post2 = Post::factory()->create([
            'title' => 'Laravel Post Two',
            'status' => 'published',
            'published_at' => now(),
            'category_id' => $category2->id,
        ]);

        $results = $this->searchService->search('Laravel', filters: ['category' => $category1->name]);

        $this->assertCount(1, $results);
        $this->assertEquals($post1->id, $results[0]->id);
    }

    public function test_search_logs_query(): void
    {
        Post::factory()->create([
            'title' => 'Laravel Testing',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->searchService->search('Laravel', logSearch: true);

        $this->assertDatabaseHas('search_logs', [
            'query' => 'Laravel',
            'search_type' => 'posts',
            'fuzzy_enabled' => true,
        ]);
    }

    public function test_search_log_records_result_count(): void
    {
        Post::factory()->count(3)->create([
            'title' => 'Laravel Post',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->searchService->search('Laravel', logSearch: true);

        $log = SearchLog::latest()->first();
        $this->assertEquals(3, $log->result_count);
    }

    public function test_search_log_records_execution_time(): void
    {
        Post::factory()->create([
            'title' => 'Laravel Testing',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->searchService->search('Laravel', logSearch: true);

        $log = SearchLog::latest()->first();
        $this->assertNotNull($log->execution_time);
        $this->assertIsFloat($log->execution_time);
    }

    public function test_search_log_associates_with_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Post::factory()->create([
            'title' => 'Laravel Testing',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->searchService->search('Laravel', logSearch: true);

        $log = SearchLog::latest()->first();
        $this->assertEquals($user->id, $log->user_id);
    }

    public function test_can_track_search_click(): void
    {
        $post = Post::factory()->create([
            'title' => 'Laravel Testing',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->searchService->search('Laravel', logSearch: true);
        $log = SearchLog::latest()->first();

        SearchClick::create([
            'search_log_id' => $log->id,
            'post_id' => $post->id,
            'position' => 1,
        ]);

        $this->assertDatabaseHas('search_clicks', [
            'search_log_id' => $log->id,
            'post_id' => $post->id,
            'position' => 1,
        ]);
    }

    public function test_search_handles_empty_query(): void
    {
        $results = $this->searchService->search('');

        $this->assertEmpty($results);
    }

    public function test_search_handles_no_results(): void
    {
        $results = $this->searchService->search('nonexistentquery12345');

        $this->assertEmpty($results);
    }

    public function test_search_can_limit_results(): void
    {
        $category = \App\Models\Category::factory()->create();

        Post::factory()->count(20)->create([
            'title' => 'Laravel Post',
            'status' => 'published',
            'published_at' => now(),
            'category_id' => $category->id,
        ]);

        $results = $this->searchService->search('Laravel', limit: 5);

        $this->assertCount(5, $results);
    }

    public function test_search_log_scope_no_results(): void
    {
        SearchLog::create([
            'query' => 'test',
            'result_count' => 0,
            'search_type' => 'posts',
            'fuzzy_enabled' => true,
        ]);

        SearchLog::create([
            'query' => 'test2',
            'result_count' => 5,
            'search_type' => 'posts',
            'fuzzy_enabled' => true,
        ]);

        $noResults = SearchLog::noResults()->get();

        $this->assertCount(1, $noResults);
        $this->assertEquals(0, $noResults->first()->result_count);
    }

    public function test_search_log_scope_recent(): void
    {
        // Create an old log outside the day range
        $oldLog = new SearchLog([
            'query' => 'old',
            'result_count' => 1,
            'search_type' => 'posts',
            'fuzzy_enabled' => true,
        ]);
        $oldLog->created_at = now()->subDays(2);
        $oldLog->updated_at = now()->subDays(2);
        $oldLog->save();

        // Create a recent log
        $recentLog = SearchLog::create([
            'query' => 'recent',
            'result_count' => 1,
            'search_type' => 'posts',
            'fuzzy_enabled' => true,
        ]);

        $dayLogs = SearchLog::recent('day')->get();
        $weekLogs = SearchLog::recent('week')->get();

        $this->assertCount(1, $dayLogs);
        $this->assertEquals('recent', $dayLogs->first()->query);
        $this->assertCount(2, $weekLogs);
    }
}
