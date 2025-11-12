<?php

namespace Tests\Feature;

use App\Models\SearchClick;
use App\Models\SearchLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchAnalyticsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_displays_analytics_for_day_period(): void
    {
        SearchLog::factory()->create([
            'query' => 'test query',
            'result_count' => 5,
            'execution_time' => 10.5,
            'user_id' => null,
            'created_at' => now()->subHours(12),
        ]);

        SearchLog::factory()->create([
            'query' => 'test query',
            'result_count' => 3,
            'execution_time' => 8.2,
            'user_id' => null,
            'created_at' => now()->subHours(6),
        ]);

        SearchLog::factory()->create([
            'query' => 'no results',
            'result_count' => 0,
            'execution_time' => 5.0,
            'user_id' => null,
            'created_at' => now()->subHours(3),
        ]);

        $this->artisan('search:analytics --period=day')
            ->expectsOutput('Search Analytics Report (day)')
            ->assertExitCode(0);
    }

    public function test_command_displays_top_queries(): void
    {
        SearchLog::factory()->create([
            'query' => 'popular query',
            'user_id' => null,
            'created_at' => now()->subHours(12),
        ]);

        SearchLog::factory()->create([
            'query' => 'popular query',
            'user_id' => null,
            'created_at' => now()->subHours(6),
        ]);

        SearchLog::factory()->create([
            'query' => 'another query',
            'user_id' => null,
            'created_at' => now()->subHours(3),
        ]);

        $this->artisan('search:analytics --period=day --top=10')
            ->expectsOutput('Top 10 Search Queries:')
            ->assertExitCode(0);
    }

    public function test_command_displays_no_result_queries(): void
    {
        SearchLog::factory()->create([
            'query' => 'no results query',
            'result_count' => 0,
            'user_id' => null,
            'created_at' => now()->subHours(12),
        ]);

        SearchLog::factory()->create([
            'query' => 'no results query',
            'result_count' => 0,
            'user_id' => null,
            'created_at' => now()->subHours(6),
        ]);

        $this->artisan('search:analytics --period=day --no-results=10')
            ->expectsOutput('Top 10 Queries with No Results:')
            ->assertExitCode(0);
    }

    public function test_command_fails_with_invalid_period(): void
    {
        $this->artisan('search:analytics --period=invalid')
            ->expectsOutput('Invalid period: invalid. Valid periods are: day, week, month, year')
            ->assertExitCode(1);
    }

    public function test_command_displays_performance_metrics(): void
    {
        SearchLog::factory()->create([
            'query' => 'query 1',
            'result_count' => 5,
            'execution_time' => 10.5,
            'user_id' => null,
            'created_at' => now()->subHours(12),
        ]);

        SearchLog::factory()->create([
            'query' => 'query 2',
            'result_count' => 0,
            'execution_time' => 5.0,
            'user_id' => null,
            'created_at' => now()->subHours(6),
        ]);

        $this->artisan('search:analytics --period=day')
            ->expectsOutput('Performance Metrics:')
            ->assertExitCode(0);
    }

    public function test_command_handles_empty_data(): void
    {
        $this->artisan('search:analytics --period=day')
            ->expectsOutput('Search Analytics Report (day)')
            ->assertExitCode(0);
    }

    public function test_command_displays_click_through_rate(): void
    {
        $user = \App\Models\User::factory()->create();
        $category = \App\Models\Category::factory()->create();
        $post = \App\Models\Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $searchLog = SearchLog::factory()->create([
            'query' => 'test query',
            'user_id' => null,
            'created_at' => now()->subHours(12),
        ]);

        SearchClick::factory()->create([
            'search_log_id' => $searchLog->id,
            'post_id' => $post->id,
            'position' => 1,
        ]);

        $this->artisan('search:analytics --period=day')
            ->expectsOutputToContain('Click-Through Rate:')
            ->assertExitCode(0);
    }

    public function test_command_uses_default_options(): void
    {
        SearchLog::factory()->create([
            'query' => 'test query',
            'user_id' => null,
            'created_at' => now()->subHours(12),
        ]);

        $this->artisan('search:analytics')
            ->expectsOutput('Search Analytics Report (day)')
            ->assertExitCode(0);
    }

    public function test_command_displays_analytics_for_week_period(): void
    {
        SearchLog::factory()->create([
            'query' => 'test query',
            'user_id' => null,
            'created_at' => now()->subDays(3),
        ]);

        $this->artisan('search:analytics --period=week')
            ->expectsOutput('Search Analytics Report (week)')
            ->assertExitCode(0);
    }

    public function test_command_displays_analytics_for_month_period(): void
    {
        SearchLog::factory()->create([
            'query' => 'test query',
            'user_id' => null,
            'created_at' => now()->subDays(15),
        ]);

        $this->artisan('search:analytics --period=month')
            ->expectsOutput('Search Analytics Report (month)')
            ->assertExitCode(0);
    }
}
