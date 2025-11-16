<?php

namespace Tests\Unit\Providers;

use App\Models\Post;
use App\Policies\PostPolicy;
use App\Services\FuzzySearchService;
use App\Services\SearchIndexService;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    public function test_search_index_service_is_singleton(): void
    {
        $first = app(SearchIndexService::class);
        $second = app(SearchIndexService::class);

        $this->assertSame($first, $second);
    }

    public function test_fuzzy_search_service_resolves_with_dependencies(): void
    {
        $service = app(FuzzySearchService::class);

        $this->assertInstanceOf(FuzzySearchService::class, $service);
    }

    public function test_post_policy_is_registered(): void
    {
        $policy = Gate::getPolicyFor(Post::class);
        $this->assertInstanceOf(PostPolicy::class, $policy);
    }
}
