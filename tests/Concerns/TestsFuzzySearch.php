<?php

namespace Tests\Concerns;

use App\Services\FuzzySearchService;
use Illuminate\Support\Facades\Cache;

trait TestsFuzzySearch
{
    protected FuzzySearchService $searchService;

    protected function setUpFuzzySearch(): void
    {
        $this->searchService = app(FuzzySearchService::class);
    }

    protected function enablePhoneticMatching(): void
    {
        config(['fuzzy-search.phonetic_enabled' => true]);
        $this->searchService = app(FuzzySearchService::class);
    }

    protected function disablePhoneticMatching(): void
    {
        config(['fuzzy-search.phonetic_enabled' => false]);
        $this->searchService = app(FuzzySearchService::class);
    }

    protected function enableSearchCache(): void
    {
        config(['fuzzy-search.cache.enabled' => true]);
        Cache::flush();
        $this->searchService = app(FuzzySearchService::class);
    }

    protected function disableSearchCache(): void
    {
        config(['fuzzy-search.cache.enabled' => false]);
        Cache::flush();
        $this->searchService = app(FuzzySearchService::class);
    }

    protected function assertSearchFinds(string $query, int $expectedPostId): void
    {
        $results = $this->searchService->search($query);

        $this->assertNotEmpty($results, "Search for '{$query}' returned no results");
        $this->assertEquals(
            $expectedPostId,
            $results[0]->id,
            "Expected post {$expectedPostId} to be first result"
        );
    }

    protected function assertSearchReturnsCount(string $query, int $expectedCount): void
    {
        $results = $this->searchService->search($query);

        $this->assertCount(
            $expectedCount,
            $results,
            "Expected {$expectedCount} results for '{$query}', got ".count($results)
        );
    }

    protected function assertSearchEmpty(string $query): void
    {
        $results = $this->searchService->search($query);

        $this->assertEmpty($results, "Expected no results for '{$query}'");
    }
}
