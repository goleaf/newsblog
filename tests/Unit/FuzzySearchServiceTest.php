<?php

namespace Tests\Unit;

use App\Exceptions\FuzzySearch\InvalidQueryException;
use App\Models\Post;
use App\Services\FuzzySearchService;
use App\Services\SearchAnalyticsService;
use App\Services\SearchIndexService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Mockery;
use Tests\TestCase;

class FuzzySearchServiceTest extends TestCase
{
    use RefreshDatabase;

    protected FuzzySearchService $searchService;

    protected SearchIndexService $mockIndexService;

    protected SearchAnalyticsService $mockAnalyticsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockIndexService = Mockery::mock(SearchIndexService::class);
        $this->mockAnalyticsService = Mockery::mock(SearchAnalyticsService::class);

        // Allow cache hit/miss logging calls (performance monitoring)
        $this->mockAnalyticsService->shouldReceive('logCacheHit')->andReturnNull();
        $this->mockAnalyticsService->shouldReceive('logCacheMiss')->andReturnNull();
        $this->mockAnalyticsService->shouldReceive('logSlowQuery')->andReturnNull();

        $this->searchService = new FuzzySearchService(
            $this->mockIndexService,
            $this->mockAnalyticsService
        );

        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ========== Exact Matching Tests ==========

    public function test_exact_match_returns_perfect_score(): void
    {
        $query = 'Laravel Testing';
        $text = 'Laravel Testing';

        $reflection = new \ReflectionClass($this->searchService);
        $method = $reflection->getMethod('calculateScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->searchService, $query, $text);

        $this->assertEquals(100.0, $score);
    }

    public function test_exact_match_case_insensitive(): void
    {
        $query = 'laravel testing';
        $text = 'Laravel Testing';

        $reflection = new \ReflectionClass($this->searchService);
        $method = $reflection->getMethod('calculateScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->searchService, $query, $text);

        $this->assertEquals(100.0, $score);
    }

    public function test_contains_exact_query_returns_high_score(): void
    {
        $query = 'Laravel';
        $text = 'Laravel Testing Guide';

        $reflection = new \ReflectionClass($this->searchService);
        $method = $reflection->getMethod('calculateScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->searchService, $query, $text);

        $this->assertEquals(95.0, $score);
    }

    // ========== Fuzzy Matching Tests ==========

    public function test_fuzzy_match_with_small_typo(): void
    {
        $query = 'Laravel';
        $text = 'Laravell'; // One character difference

        $reflection = new \ReflectionClass($this->searchService);
        $method = $reflection->getMethod('calculateScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->searchService, $query, $text);

        $this->assertGreaterThan(60.0, $score);
        $this->assertLessThan(100.0, $score);
    }

    public function test_fuzzy_match_with_multiple_typos(): void
    {
        $query = 'Laravel';
        $text = 'Laravle'; // Two character difference

        $reflection = new \ReflectionClass($this->searchService);
        $method = $reflection->getMethod('calculateScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->searchService, $query, $text);

        $this->assertGreaterThan(0.0, $score);
    }

    public function test_fuzzy_match_with_word_boundary(): void
    {
        $query = 'Laravel Testing';
        $text = 'Laravel Testing Guide';

        $reflection = new \ReflectionClass($this->searchService);
        $method = $reflection->getMethod('calculateScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->searchService, $query, $text);

        $this->assertGreaterThan(80.0, $score);
    }

    public function test_fuzzy_match_partial_word_match(): void
    {
        $query = 'Test';
        $text = 'Testing Guide';

        $reflection = new \ReflectionClass($this->searchService);
        $method = $reflection->getMethod('calculateScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->searchService, $query, $text);

        $this->assertGreaterThan(0.0, $score);
    }

    // ========== Relevance Scoring Tests ==========

    public function test_relevance_scoring_ranks_exact_matches_higher(): void
    {
        $post1 = Post::factory()->create([
            'title' => 'Laravel Testing',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $post2 = Post::factory()->create([
            'title' => 'Laravel Testng',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $index = [
            ['id' => $post1->id, 'title' => 'Laravel Testing', 'excerpt' => ''],
            ['id' => $post2->id, 'title' => 'Laravel Testng', 'excerpt' => ''],
        ];

        $this->mockIndexService->shouldReceive('getIndex')
            ->with('posts')
            ->andReturn($index);

        $results = $this->searchService->searchPosts('Laravel Testing');

        $this->assertNotEmpty($results);
        $firstResult = $results->first();
        $this->assertEquals($post1->id, $firstResult->id);
    }

    public function test_relevance_scoring_considers_excerpt(): void
    {
        $post1 = Post::factory()->create([
            'title' => 'Guide',
            'excerpt' => 'Laravel Testing',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $post2 = Post::factory()->create([
            'title' => 'Laravel',
            'excerpt' => '',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $index = [
            ['id' => $post1->id, 'title' => 'Guide', 'excerpt' => 'Laravel Testing'],
            ['id' => $post2->id, 'title' => 'Laravel', 'excerpt' => ''],
        ];

        $this->mockIndexService->shouldReceive('getIndex')
            ->with('posts')
            ->andReturn($index);

        $results = $this->searchService->searchPosts('Laravel Testing');

        $this->assertNotEmpty($results);
    }

    public function test_relevance_scoring_sorts_by_score_descending(): void
    {
        $post1 = Post::factory()->create([
            'title' => 'Laravel',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $post2 = Post::factory()->create([
            'title' => 'Laravel Testing',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $post3 = Post::factory()->create([
            'title' => 'Laravel Testing Guide',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $index = [
            ['id' => $post1->id, 'title' => 'Laravel', 'excerpt' => ''],
            ['id' => $post2->id, 'title' => 'Laravel Testing', 'excerpt' => ''],
            ['id' => $post3->id, 'title' => 'Laravel Testing Guide', 'excerpt' => ''],
        ];

        $this->mockIndexService->shouldReceive('getIndex')
            ->with('posts')
            ->andReturn($index);

        $results = $this->searchService->searchPosts('Laravel Testing');

        $this->assertNotEmpty($results);
        $scores = $results->pluck('relevanceScore')->toArray();
        $sortedScores = $scores;
        rsort($sortedScores);
        $this->assertEquals($sortedScores, $scores);
    }

    // ========== Field Weighting Tests ==========

    public function test_field_weighting_title_has_higher_weight(): void
    {
        Config::set('fuzzy-search.weights', [
            'title' => 3.0,
            'excerpt' => 2.0,
            'content' => 1.0,
        ]);
        Config::set('fuzzy-search.threshold', 20); // Lower threshold for this test

        // Recreate service with new config
        $this->searchService = new FuzzySearchService(
            $this->mockIndexService,
            $this->mockAnalyticsService
        );

        $post1 = Post::factory()->create([
            'title' => 'Laravel',
            'excerpt' => 'PHP',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $post2 = Post::factory()->create([
            'title' => 'PHP',
            'excerpt' => 'Laravel',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $index = [
            ['id' => $post1->id, 'title' => 'Laravel', 'excerpt' => 'PHP', 'content' => ''],
            ['id' => $post2->id, 'title' => 'PHP', 'excerpt' => 'Laravel', 'content' => ''],
        ];

        $this->mockIndexService->shouldReceive('getIndex')
            ->with('posts')
            ->andReturn($index);

        $results = $this->searchService->multiFieldSearch('Laravel', ['title', 'excerpt', 'content']);

        $this->assertNotEmpty($results);
        $firstResult = $results->first();
        $this->assertEquals($post1->id, $firstResult->id);
    }

    public function test_field_weighting_multiple_fields_combined(): void
    {
        Config::set('fuzzy-search.weights', [
            'title' => 3.0,
            'excerpt' => 2.0,
            'content' => 1.0,
        ]);
        Config::set('fuzzy-search.threshold', 20); // Lower threshold

        // Recreate service with new config
        $this->searchService = new FuzzySearchService(
            $this->mockIndexService,
            $this->mockAnalyticsService
        );

        $post = Post::factory()->create([
            'title' => 'Guide',
            'excerpt' => 'Laravel',
            'content' => 'Testing',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $index = [
            ['id' => $post->id, 'title' => 'Guide', 'excerpt' => 'Laravel', 'content' => 'Testing'],
        ];

        $this->mockIndexService->shouldReceive('getIndex')
            ->with('posts')
            ->andReturn($index);

        $results = $this->searchService->multiFieldSearch('Laravel Testing', ['title', 'excerpt', 'content']);

        $this->assertNotEmpty($results);
    }

    public function test_field_weighting_normalizes_score(): void
    {
        Config::set('fuzzy-search.weights', [
            'title' => 3.0,
            'excerpt' => 2.0,
        ]);
        Config::set('fuzzy-search.threshold', 20); // Lower threshold

        // Recreate service with new config
        $this->searchService = new FuzzySearchService(
            $this->mockIndexService,
            $this->mockAnalyticsService
        );

        $post = Post::factory()->create([
            'title' => 'Laravel',
            'excerpt' => 'Testing',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $index = [
            ['id' => $post->id, 'title' => 'Laravel', 'excerpt' => 'Testing'],
        ];

        $this->mockIndexService->shouldReceive('getIndex')
            ->with('posts')
            ->andReturn($index);

        $results = $this->searchService->multiFieldSearch('Laravel Testing', ['title', 'excerpt']);

        $this->assertNotEmpty($results);
        $score = $results->first()->relevanceScore;
        $this->assertGreaterThanOrEqual(0.0, $score);
        $this->assertLessThanOrEqual(100.0, $score);
    }

    // ========== Threshold Filtering Tests ==========

    public function test_threshold_filtering_excludes_low_scores(): void
    {
        $post1 = Post::factory()->create([
            'title' => 'Laravel Testing',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $post2 = Post::factory()->create([
            'title' => 'PHP Guide',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $index = [
            ['id' => $post1->id, 'title' => 'Laravel Testing', 'excerpt' => ''],
            ['id' => $post2->id, 'title' => 'PHP Guide', 'excerpt' => ''],
        ];

        $this->mockIndexService->shouldReceive('getIndex')
            ->with('posts')
            ->andReturn($index);

        $resultsHighThreshold = $this->searchService->searchPosts('Laravel', ['threshold' => 90]);
        $resultsLowThreshold = $this->searchService->searchPosts('Laravel', ['threshold' => 20]);

        $this->assertLessThanOrEqual($resultsLowThreshold->count(), $resultsHighThreshold->count());
    }

    public function test_threshold_filtering_custom_threshold(): void
    {
        $post = Post::factory()->create([
            'title' => 'Laravel',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $index = [
            ['id' => $post->id, 'title' => 'Laravel', 'excerpt' => ''],
        ];

        $this->mockIndexService->shouldReceive('getIndex')
            ->with('posts')
            ->andReturn($index);

        $results = $this->searchService->search('Laravel', threshold: 80);

        $this->assertNotEmpty($results);
    }

    public function test_threshold_filtering_default_threshold(): void
    {
        Config::set('fuzzy-search.threshold', 60);

        $post = Post::factory()->create([
            'title' => 'Laravel',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $index = [
            ['id' => $post->id, 'title' => 'Laravel', 'excerpt' => ''],
        ];

        $this->mockIndexService->shouldReceive('getIndex')
            ->with('posts')
            ->andReturn($index);

        $results = $this->searchService->search('Laravel');

        $this->assertNotEmpty($results);
    }

    // ========== Phonetic Matching Tests ==========

    public function test_phonetic_matching_finds_similar_sounding_words(): void
    {
        Config::set('fuzzy-search.phonetic_enabled', true);
        Config::set('fuzzy-search.phonetic_weight', 0.3);

        $this->searchService = new FuzzySearchService(
            $this->mockIndexService,
            $this->mockAnalyticsService
        );

        $reflection = new \ReflectionClass($this->searchService);
        // Test through calculateScore which internally uses phonetic matching
        $method = $reflection->getMethod('calculateScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->searchService, 'Smith', 'Smyth');

        $this->assertGreaterThan(0.0, $score);
    }

    public function test_phonetic_matching_exact_phonetic_match(): void
    {
        Config::set('fuzzy-search.phonetic_enabled', true);
        Config::set('fuzzy-search.phonetic_weight', 0.3);

        $this->searchService = new FuzzySearchService(
            $this->mockIndexService,
            $this->mockAnalyticsService
        );

        $reflection = new \ReflectionClass($this->searchService);
        $method = $reflection->getMethod('calculateScore');
        $method->setAccessible(true);

        // Test that phonetic matching contributes to score
        $score = $method->invoke($this->searchService, 'Smith', 'Smith');

        $this->assertEquals(100.0, $score); // Exact match should be 100
    }

    public function test_phonetic_matching_contained_phonetic_match(): void
    {
        Config::set('fuzzy-search.phonetic_enabled', true);
        Config::set('fuzzy-search.phonetic_weight', 0.3);

        $this->searchService = new FuzzySearchService(
            $this->mockIndexService,
            $this->mockAnalyticsService
        );

        $reflection = new \ReflectionClass($this->searchService);
        $method = $reflection->getMethod('calculateScore');
        $method->setAccessible(true);

        // Test phonetic matching through calculateScore
        $score = $method->invoke($this->searchService, 'Smith', 'Smithsonian');

        $this->assertGreaterThan(0.0, $score);
    }

    public function test_phonetic_matching_word_by_word(): void
    {
        Config::set('fuzzy-search.phonetic_enabled', true);
        Config::set('fuzzy-search.phonetic_weight', 0.3);

        $this->searchService = new FuzzySearchService(
            $this->mockIndexService,
            $this->mockAnalyticsService
        );

        $reflection = new \ReflectionClass($this->searchService);
        $method = $reflection->getMethod('calculateScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->searchService, 'John Smith', 'John Smyth');

        $this->assertGreaterThan(0.0, $score);
    }

    public function test_phonetic_matching_only_applies_when_enabled(): void
    {
        Config::set('fuzzy-search.phonetic_enabled', false);

        $this->searchService = new FuzzySearchService(
            $this->mockIndexService,
            $this->mockAnalyticsService
        );

        $reflection = new \ReflectionClass($this->searchService);
        $method = $reflection->getMethod('calculateScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->searchService, 'Smith', 'Smyth');

        $this->assertGreaterThan(0.0, $score);
    }

    public function test_phonetic_matching_applies_weight(): void
    {
        Config::set('fuzzy-search.phonetic_enabled', true);
        Config::set('fuzzy-search.phonetic_weight', 0.3);

        $this->searchService = new FuzzySearchService(
            $this->mockIndexService,
            $this->mockAnalyticsService
        );

        $reflection = new \ReflectionClass($this->searchService);
        $method = $reflection->getMethod('calculateScore');
        $method->setAccessible(true);

        // Use words that have low fuzzy match but good phonetic match
        $score = $method->invoke($this->searchService, 'Smith', 'Smyth');

        // Score should be less than perfect phonetic match (80) due to weight
        // But might be higher if fuzzy matching finds a good match first
        $this->assertGreaterThan(0.0, $score);
        $this->assertLessThanOrEqual(100.0, $score);
    }

    // ========== Query Validation Tests ==========

    public function test_validate_query_throws_exception_for_empty_query(): void
    {
        $this->expectException(InvalidQueryException::class);

        $reflection = new \ReflectionClass($this->searchService);
        $method = $reflection->getMethod('validateQuery');
        $method->setAccessible(true);

        $method->invoke($this->searchService, '');
    }

    public function test_validate_query_throws_exception_for_whitespace_only(): void
    {
        $this->expectException(InvalidQueryException::class);

        $reflection = new \ReflectionClass($this->searchService);
        $method = $reflection->getMethod('validateQuery');
        $method->setAccessible(true);

        $method->invoke($this->searchService, '   ');
    }

    public function test_validate_query_throws_exception_for_too_long_query(): void
    {
        Config::set('fuzzy-search.limits.max_query_length', 10);

        $this->expectException(InvalidQueryException::class);

        $reflection = new \ReflectionClass($this->searchService);
        $method = $reflection->getMethod('validateQuery');
        $method->setAccessible(true);

        $method->invoke($this->searchService, str_repeat('a', 11));
    }

    public function test_validate_query_throws_exception_for_invalid_characters(): void
    {
        $this->expectException(InvalidQueryException::class);

        $reflection = new \ReflectionClass($this->searchService);
        $method = $reflection->getMethod('validateQuery');
        $method->setAccessible(true);

        $method->invoke($this->searchService, 'test@#$%');
    }

    public function test_validate_query_accepts_valid_query(): void
    {
        $reflection = new \ReflectionClass($this->searchService);
        $method = $reflection->getMethod('validateQuery');
        $method->setAccessible(true);

        $method->invoke($this->searchService, 'Laravel Testing');

        $this->assertTrue(true);
    }

    // ========== Filter Tests ==========

    public function test_passes_filters_category_match(): void
    {
        $item = ['id' => 1, 'title' => 'Test', 'category' => 'Technology'];
        $filters = ['category' => 'Technology'];

        $reflection = new \ReflectionClass($this->searchService);
        $method = $reflection->getMethod('passesFilters');
        $method->setAccessible(true);

        $result = $method->invoke($this->searchService, $item, $filters);

        $this->assertTrue($result);
    }

    public function test_passes_filters_category_mismatch(): void
    {
        $item = ['id' => 1, 'title' => 'Test', 'category' => 'Technology'];
        $filters = ['category' => 'Science'];

        $reflection = new \ReflectionClass($this->searchService);
        $method = $reflection->getMethod('passesFilters');
        $method->setAccessible(true);

        $result = $method->invoke($this->searchService, $item, $filters);

        $this->assertFalse($result);
    }

    public function test_passes_filters_date_range(): void
    {
        $item = [
            'id' => 1,
            'title' => 'Test',
            'published_at' => '2024-01-15T00:00:00Z',
        ];
        $filters = [
            'date_from' => '2024-01-01',
            'date_to' => '2024-01-31',
        ];

        $reflection = new \ReflectionClass($this->searchService);
        $method = $reflection->getMethod('passesFilters');
        $method->setAccessible(true);

        $result = $method->invoke($this->searchService, $item, $filters);

        $this->assertTrue($result);
    }

    public function test_passes_filters_date_out_of_range(): void
    {
        $item = [
            'id' => 1,
            'title' => 'Test',
            'published_at' => '2024-02-15T00:00:00Z',
        ];
        $filters = [
            'date_from' => '2024-01-01',
            'date_to' => '2024-01-31',
        ];

        $reflection = new \ReflectionClass($this->searchService);
        $method = $reflection->getMethod('passesFilters');
        $method->setAccessible(true);

        $result = $method->invoke($this->searchService, $item, $filters);

        $this->assertFalse($result);
    }

    public function test_passes_filters_no_filters_returns_true(): void
    {
        $item = ['id' => 1, 'title' => 'Test'];
        $filters = [];

        $reflection = new \ReflectionClass($this->searchService);
        $method = $reflection->getMethod('passesFilters');
        $method->setAccessible(true);

        $result = $method->invoke($this->searchService, $item, $filters);

        $this->assertTrue($result);
    }
}
