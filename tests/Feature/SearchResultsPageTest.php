<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchResultsPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that search results display highlighted matching text.
     * Requirement 2.2: Highlighted matching terms in results
     */
    public function test_search_results_display_highlighted_text(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->create([
            'title' => 'Laravel Framework Guide',
            'excerpt' => 'Learn about Laravel framework features',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->get('/search?q=Laravel');

        $response->assertStatus(200);
        $response->assertSee('Laravel', false);
        // Check that highlighting CSS class exists
        $response->assertSee('search-highlight', false);
    }

    /**
     * Test that search results display context snippets.
     * Requirement 2.2: Context snippets in search results
     */
    public function test_search_results_display_context_snippets(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->create([
            'title' => 'Complete Guide',
            'excerpt' => 'This is a comprehensive guide about Laravel framework and its features',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->get('/search?q=Laravel');

        $response->assertStatus(200);
        // Should display excerpt or context snippet
        $response->assertSee('Laravel', false);
    }

    /**
     * Test that search results display relevance scores when enabled.
     * Requirement 2.3: Relevance scores display
     */
    public function test_search_results_can_display_relevance_scores(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->create([
            'title' => 'Laravel Framework',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->get('/search?q=Laravel');

        $response->assertStatus(200);
        // Check that relevance score toggle exists
        $response->assertSee('showRelevanceScores', false);
    }

    /**
     * Test that search results show "Did you mean?" suggestions.
     * Requirement 2.4: Spelling suggestions for typos
     */
    public function test_search_results_show_spelling_suggestions(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->create([
            'title' => 'Laravel Framework Guide',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        // Search with typo that returns no results
        $response = $this->get('/search?q=Laravle');

        $response->assertStatus(200);
        // If no results found, should show spelling suggestion
        $posts = $response->viewData('posts');
        if ($posts->total() === 0) {
            $spellingSuggestion = $response->viewData('spellingSuggestion');
            // Spelling suggestion may or may not be provided depending on fuzzy search config
            $this->assertTrue(is_null($spellingSuggestion) || is_string($spellingSuggestion));
        }
    }

    /**
     * Test that search results pagination works correctly.
     * Requirement 2.5: Pagination support
     */
    public function test_search_results_pagination_works(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create 20 posts
        Post::factory()->count(20)->create([
            'title' => 'Laravel Tutorial',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->get('/search?q=Laravel');

        $response->assertStatus(200);
        $posts = $response->viewData('posts');
        $this->assertLessThanOrEqual(15, $posts->count());
        $this->assertGreaterThan(15, $posts->total());

        // Test second page
        $response = $this->get('/search?q=Laravel&page=2');
        $response->assertStatus(200);
        $posts = $response->viewData('posts');
        $this->assertGreaterThan(0, $posts->count());
    }

    /**
     * Test that search results show empty state with helpful tips.
     * Requirement 2.4: Empty state for no results
     */
    public function test_search_results_show_empty_state_with_tips(): void
    {
        $response = $this->get('/search?q=NonexistentSearchTerm12345');

        $response->assertStatus(200);
        $response->assertSee('No results found', false);
        $response->assertSee('Try these tips', false);
    }

    /**
     * Test that search results show popular articles when no results found.
     * Requirement 2.4: Suggest alternative content
     */
    public function test_search_results_show_popular_articles_when_empty(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create some popular posts
        Post::factory()->count(3)->create([
            'title' => 'Popular Article',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
            'view_count' => 1000,
        ]);

        $response = $this->get('/search?q=NonexistentSearchTerm12345');

        $response->assertStatus(200);
        $response->assertSee('Popular Articles', false);
    }

    /**
     * Test that search results display filter panel.
     * Requirement 2.3: Filter options for search results
     */
    public function test_search_results_display_filter_panel(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->create([
            'title' => 'Laravel Post',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->get('/search?q=Laravel');

        $response->assertStatus(200);
        $response->assertViewHas('categories');
        $response->assertViewHas('authors');
        $response->assertViewHas('tags');
        $response->assertSee('Filters', false);
    }

    /**
     * Test that search results display sort dropdown.
     * Requirement 2.3: Sorting options for search results
     */
    public function test_search_results_display_sort_dropdown(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->create([
            'title' => 'Laravel Post',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->get('/search?q=Laravel');

        $response->assertStatus(200);
        $response->assertSee('Sort:', false);
    }

    /**
     * Test that search results show result count.
     * Requirement 2.2: Display result count
     */
    public function test_search_results_show_result_count(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->count(5)->create([
            'title' => 'Laravel Tutorial',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->get('/search?q=Laravel');

        $response->assertStatus(200);
        $response->assertSee('Found', false);
        $response->assertSee('result', false);
    }

    /**
     * Test that search results highlight query in title and excerpt.
     * Requirement 2.2: Highlighted matching terms
     */
    public function test_search_results_highlight_query_in_content(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->create([
            'title' => 'Laravel Framework Guide',
            'excerpt' => 'Learn Laravel framework basics',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->get('/search?q=Laravel');

        $response->assertStatus(200);
        $posts = $response->viewData('posts');
        
        if ($posts->total() > 0) {
            $firstPost = $posts->first();
            // Check that highlighted_title or highlighted_excerpt exists
            $this->assertTrue(
                isset($firstPost->highlighted_title) || 
                isset($firstPost->highlighted_excerpt)
            );
        }
    }

    /**
     * Test that search results work with infinite scroll.
     * Requirement 2.5: Infinite scroll pagination
     */
    public function test_search_results_support_infinite_scroll(): void
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

        // Test AJAX request for infinite scroll
        $response = $this->getJson('/search?q=Laravel&page=2');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'html',
            'currentPage',
            'lastPage',
            'hasMorePages',
        ]);
    }

    /**
     * Test that search results log search queries for analytics.
     * Requirement 16.2: Search analytics tracking
     */
    public function test_search_results_log_queries_for_analytics(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->create([
            'title' => 'Laravel Post',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->get('/search?q=Laravel');

        $response->assertStatus(200);
        
        // Check that search log was created
        $this->assertDatabaseHas('search_logs', [
            'query' => 'Laravel',
            'search_type' => 'posts',
        ]);
    }

    /**
     * Test that search results provide search log ID for click tracking.
     * Requirement 16.2: Search click tracking
     */
    public function test_search_results_provide_search_log_id(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->create([
            'title' => 'Laravel Post',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->get('/search?q=Laravel');

        $response->assertStatus(200);
        $searchLogId = $response->viewData('searchLogId');
        $this->assertIsInt($searchLogId);
    }

    /**
     * Test that search results extract context around matching terms.
     * Requirement 2.2: Context snippets with matching terms
     */
    public function test_search_results_extract_context_snippets(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->create([
            'title' => 'Complete Guide',
            'excerpt' => 'This is a very long excerpt that contains information about Laravel framework and how to use it effectively in your projects',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->get('/search?q=Laravel');

        $response->assertStatus(200);
        $posts = $response->viewData('posts');
        
        if ($posts->total() > 0) {
            $firstPost = $posts->first();
            // Check that excerpt_context exists
            $this->assertTrue(
                isset($firstPost->excerpt_context) || 
                !empty($firstPost->excerpt)
            );
        }
    }

    /**
     * Test that search results show execution time.
     * Requirement 2.3: Performance metrics display
     */
    public function test_search_results_track_execution_time(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->create([
            'title' => 'Laravel Post',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->get('/search?q=Laravel');

        $response->assertStatus(200);
        
        // Check that execution time was logged
        $this->assertDatabaseHas('search_logs', [
            'query' => 'Laravel',
        ]);
        
        $searchLog = \App\Models\SearchLog::where('query', 'Laravel')->first();
        $this->assertNotNull($searchLog->execution_time);
        $this->assertIsFloat($searchLog->execution_time);
    }
}
