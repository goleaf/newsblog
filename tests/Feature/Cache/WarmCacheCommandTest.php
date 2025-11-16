<?php

namespace Tests\Feature\Cache;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class WarmCacheCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_cache_warm_command_populates_expected_keys(): void
    {
        Cache::flush();

        $exit = Artisan::call('cache:warm');
        $this->assertSame(0, $exit);

        $this->assertTrue(Cache::has('query.home.featured'));
        $this->assertTrue(Cache::has('query.home.trending'));
        $this->assertTrue(Cache::has('query.home.category-sections'));
        $this->assertTrue(Cache::has('query.category-tree'));
        $this->assertTrue(Cache::has('query.menu.primary'));
    }
}



