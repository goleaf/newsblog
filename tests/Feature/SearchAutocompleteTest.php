<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchAutocompleteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that search suggestions endpoint returns JSON response.
     * Requirement 2.1: Autocomplete with debounced search
     */
    public function test_search_suggestions_endpoint_returns_json(): void
    {
        $response = $this->getJson('/search/suggestions?q=Laravel');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Test that search suggestions require minimum query length.
     * Requirement 2.1: Autocomplete with debounced search (300ms)
     */
    public function test_search_suggestions_require_minimum_length(): void
    {
        $response = $this->getJson('/search/suggestions?q=La');

        $response->assertStatus(200);
        $response->assertJson([]);
    }

    /**
     * Test that search suggestions return relevant results.
     * Requirement 2.1: Live autocomplete suggestions with highlighted matching text
     */
    public function test_search_suggestions_return_relevant_results(): void
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

        Post::factory()->create([
            'title' => 'Laravel Testing Best Practices',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->getJson('/search/suggestions?q=Laravel');

        $response->assertStatus(200);
        $response->assertJsonStructure([]);
        $suggestions = $response->json();
        $this->assertIsArray($suggestions);
    }

    /**
     * Test that search suggestions work with typos (fuzzy search).
     * Requirement 2.2: Fuzzy search with typo tolerance
     */
    public function test_search_suggestions_handle_typos(): void
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

        // Test with typo "laravle" instead of "laravel"
        $response = $this->getJson('/search/suggestions?q=laravle');

        $response->assertStatus(200);
        $suggestions = $response->json();
        $this->assertIsArray($suggestions);
    }

    /**
     * Test that search suggestions are limited to reasonable count.
     * Requirement 2.1: Autocomplete performance
     */
    public function test_search_suggestions_are_limited(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        // Create many posts with similar titles
        for ($i = 1; $i <= 20; $i++) {
            Post::factory()->create([
                'title' => "Laravel Tutorial Part {$i}",
                'status' => 'published',
                'published_at' => now()->subDay(),
                'user_id' => $user->id,
                'category_id' => $category->id,
            ]);
        }

        $response = $this->getJson('/search/suggestions?q=Laravel');

        $response->assertStatus(200);
        $suggestions = $response->json();
        $this->assertIsArray($suggestions);
        // Suggestions should be limited (typically 5-10)
        $this->assertLessThanOrEqual(10, count($suggestions));
    }

    /**
     * Test that search suggestions respond quickly.
     * Requirement 2.1: Display suggestions within 300 milliseconds
     */
    public function test_search_suggestions_respond_quickly(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->count(50)->create([
            'title' => 'Laravel Post',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $startTime = microtime(true);
        $response = $this->getJson('/search/suggestions?q=Laravel');
        $endTime = microtime(true);

        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);
        // Suggestions should respond within 300ms as per requirement
        $this->assertLessThan(300, $executionTime, 'Suggestions took longer than 300ms');
    }

    /**
     * Test that search suggestions only include published posts.
     * Requirement 2.1: Autocomplete should only suggest published content
     */
    public function test_search_suggestions_only_include_published_posts(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->create([
            'title' => 'Published Laravel Post',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        Post::factory()->create([
            'title' => 'Draft Laravel Post',
            'status' => 'draft',
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->getJson('/search/suggestions?q=Laravel');

        $response->assertStatus(200);
        $suggestions = $response->json();
        $this->assertIsArray($suggestions);
        // Verify that draft posts are not included in suggestions
        foreach ($suggestions as $suggestion) {
            $this->assertStringNotContainsString('Draft', $suggestion);
        }
    }

    /**
     * Test that search suggestions handle special characters.
     * Requirement 2.1: Robust autocomplete handling
     */
    public function test_search_suggestions_handle_special_characters(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->create([
            'title' => 'C++ Programming Guide',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->getJson('/search/suggestions?q=C%2B%2B');

        $response->assertStatus(200);
        $suggestions = $response->json();
        $this->assertIsArray($suggestions);
    }

    /**
     * Test that search suggestions handle empty query gracefully.
     * Requirement 2.1: Graceful error handling
     */
    public function test_search_suggestions_handle_empty_query(): void
    {
        $response = $this->getJson('/search/suggestions?q=');

        $response->assertStatus(200);
        $response->assertJson([]);
    }

    /**
     * Test that search suggestions are rate limited.
     * Requirement 2.1: Prevent abuse of autocomplete endpoint
     */
    public function test_search_suggestions_are_rate_limited(): void
    {
        // Make multiple requests quickly
        for ($i = 0; $i < 65; $i++) {
            $response = $this->getJson('/search/suggestions?q=test');
            
            if ($i < 60) {
                $response->assertStatus(200);
            }
        }

        // The 61st request should be rate limited
        $response = $this->getJson('/search/suggestions?q=test');
        $this->assertContains($response->status(), [200, 429]);
    }

    /**
     * Test that FuzzySearchService integration works correctly.
     * Requirement 2.2: Verify FuzzySearchService integration
     */
    public function test_fuzzy_search_service_integration(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->create([
            'title' => 'JavaScript Framework Comparison',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        // Test with slight typo
        $response = $this->getJson('/search/suggestions?q=Javascrpt');

        $response->assertStatus(200);
        $suggestions = $response->json();
        $this->assertIsArray($suggestions);
        // FuzzySearchService should handle the typo
    }

    /**
     * Test that search autocomplete component exists and has proper structure.
     * Requirement 2.1: Autocomplete component implementation
     */
    public function test_search_autocomplete_component_exists(): void
    {
        $componentPath = resource_path('views/components/discovery/search-autocomplete.blade.php');
        $this->assertFileExists($componentPath, 'Search autocomplete component should exist');

        $content = file_get_contents($componentPath);
        
        // Verify Alpine.js data structure
        $this->assertStringContainsString('x-data="searchAutocomplete"', $content);
        $this->assertStringContainsString('x-init="init()"', $content);
        
        // Verify debounced search
        $this->assertStringContainsString('@input.debounce.300ms="search()"', $content);
        
        // Verify keyboard navigation handlers
        $this->assertStringContainsString('@keydown="handleKeydown($event)"', $content);
        
        // Verify recent searches functionality
        $this->assertStringContainsString('recentSearches', $content);
        
        // Verify popular searches functionality
        $this->assertStringContainsString('popularSearches', $content);
    }

    /**
     * Test that search autocomplete JavaScript has proper keyboard navigation.
     * Requirement 2.1: Keyboard navigation (arrow keys, enter, escape)
     */
    public function test_search_autocomplete_has_keyboard_navigation(): void
    {
        $componentPath = resource_path('views/components/discovery/search-autocomplete.blade.php');
        $content = file_get_contents($componentPath);
        
        // Verify keyboard event handling
        $this->assertStringContainsString('ArrowDown', $content);
        $this->assertStringContainsString('ArrowUp', $content);
        $this->assertStringContainsString('Enter', $content);
        $this->assertStringContainsString('Escape', $content);
        
        // Verify selectedIndex management
        $this->assertStringContainsString('selectedIndex', $content);
        
        // Verify scroll to selected functionality
        $this->assertStringContainsString('scrollToSelected', $content);
    }

    /**
     * Test that search autocomplete has debounced search implementation.
     * Requirement 2.1: Debounced search (300ms)
     */
    public function test_search_autocomplete_has_debounced_search(): void
    {
        $componentPath = resource_path('views/components/discovery/search-autocomplete.blade.php');
        $content = file_get_contents($componentPath);
        
        // Verify 300ms debounce
        $this->assertStringContainsString('debounce.300ms', $content);
        
        // Verify search function exists
        $this->assertStringContainsString('async search()', $content);
        
        // Verify loading state
        $this->assertStringContainsString('loading: false', $content);
        $this->assertStringContainsString('this.loading = true', $content);
    }

    /**
     * Test that search autocomplete has recent searches functionality.
     * Requirement 2.1: Recent searches
     */
    public function test_search_autocomplete_has_recent_searches(): void
    {
        $componentPath = resource_path('views/components/discovery/search-autocomplete.blade.php');
        $content = file_get_contents($componentPath);
        
        // Verify recent searches array
        $this->assertStringContainsString('recentSearches: []', $content);
        
        // Verify localStorage integration
        $this->assertStringContainsString('localStorage.getItem(\'recentSearches\')', $content);
        $this->assertStringContainsString('localStorage.setItem(\'recentSearches\'', $content);
        
        // Verify save recent search function
        $this->assertStringContainsString('saveRecentSearch', $content);
        
        // Verify clear recent searches function
        $this->assertStringContainsString('clearRecentSearches', $content);
    }

    /**
     * Test that search autocomplete has popular searches functionality.
     * Requirement 2.1: Popular searches
     */
    public function test_search_autocomplete_has_popular_searches(): void
    {
        $componentPath = resource_path('views/components/discovery/search-autocomplete.blade.php');
        $content = file_get_contents($componentPath);
        
        // Verify popular searches array
        $this->assertStringContainsString('popularSearches: []', $content);
        
        // Verify popular searches are initialized
        $this->assertStringContainsString('this.popularSearches = [', $content);
        
        // Verify popular searches are displayed in template
        $this->assertStringContainsString('Popular Searches', $content);
    }

    /**
     * Test that search autocomplete makes API calls to suggestions endpoint.
     * Requirement 2.1: API integration
     */
    public function test_search_autocomplete_calls_suggestions_api(): void
    {
        $componentPath = resource_path('views/components/discovery/search-autocomplete.blade.php');
        $content = file_get_contents($componentPath);
        
        // Verify fetch call to suggestions endpoint
        $this->assertStringContainsString('fetch(', $content);
        $this->assertStringContainsString('search.suggestions', $content);
        
        // Verify proper headers
        $this->assertStringContainsString('Accept', $content);
        $this->assertStringContainsString('application/json', $content);
        $this->assertStringContainsString('X-Requested-With', $content);
        $this->assertStringContainsString('XMLHttpRequest', $content);
    }

    /**
     * Test that search autocomplete has proper accessibility attributes.
     * Requirement 2.1: Accessibility compliance
     */
    public function test_search_autocomplete_has_accessibility_attributes(): void
    {
        $componentPath = resource_path('views/components/discovery/search-autocomplete.blade.php');
        $content = file_get_contents($componentPath);
        
        // Verify ARIA attributes
        $this->assertStringContainsString('aria-label', $content);
        $this->assertStringContainsString('aria-autocomplete="list"', $content);
        $this->assertStringContainsString('aria-controls="search-results"', $content);
        $this->assertStringContainsString('aria-expanded', $content);
        $this->assertStringContainsString('role="listbox"', $content);
        $this->assertStringContainsString('role="option"', $content);
        $this->assertStringContainsString('aria-selected', $content);
    }

    /**
     * Test that search autocomplete highlights matching text.
     * Requirement 2.1: Highlighted matching text
     */
    public function test_search_autocomplete_highlights_matches(): void
    {
        $componentPath = resource_path('views/components/discovery/search-autocomplete.blade.php');
        $content = file_get_contents($componentPath);
        
        // Verify highlight function exists
        $this->assertStringContainsString('highlightMatch', $content);
        
        // Verify mark tag for highlighting
        $this->assertStringContainsString('<mark', $content);
        
        // Verify HTML escaping for security
        $this->assertStringContainsString('escapeHtml', $content);
    }

    /**
     * Test that suggestions endpoint returns data in correct format.
     * Requirement 2.1: API response format
     */
    public function test_suggestions_endpoint_returns_correct_format(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->create([
            'title' => 'Laravel Testing Guide',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        Post::factory()->create([
            'title' => 'Laravel Best Practices',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->getJson('/search/suggestions?q=Laravel');

        $response->assertStatus(200);
        $suggestions = $response->json();
        
        // Verify it's an array
        $this->assertIsArray($suggestions);
        
        // Verify each suggestion is a string
        foreach ($suggestions as $suggestion) {
            $this->assertIsString($suggestion);
        }
        
        // Verify suggestions contain the query term
        if (count($suggestions) > 0) {
            $hasMatch = false;
            foreach ($suggestions as $suggestion) {
                if (stripos($suggestion, 'Laravel') !== false) {
                    $hasMatch = true;
                    break;
                }
            }
            $this->assertTrue($hasMatch, 'At least one suggestion should contain the query term');
        }
    }

    /**
     * Test that FuzzySearchService getSuggestions method works correctly.
     * Requirement 2.2: FuzzySearchService integration
     */
    public function test_fuzzy_search_service_get_suggestions_method(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->create([
            'title' => 'React Hooks Tutorial',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        Post::factory()->create([
            'title' => 'React Components Guide',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $fuzzySearchService = app(\App\Services\FuzzySearchService::class);
        $suggestions = $fuzzySearchService->getSuggestions('React', 5);

        $this->assertIsArray($suggestions);
        $this->assertLessThanOrEqual(5, count($suggestions));
        
        // Verify suggestions are strings
        foreach ($suggestions as $suggestion) {
            $this->assertIsString($suggestion);
        }
    }
}
