<?php

namespace Tests\Feature;

use App\Models\SearchLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArchiveSearchLogsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_archives_old_search_logs(): void
    {
        SearchLog::factory()->create([
            'query' => 'old query',
            'user_id' => null,
            'created_at' => now()->subDays(100),
        ]);

        SearchLog::factory()->create([
            'query' => 'recent query',
            'user_id' => null,
            'created_at' => now()->subDays(30),
        ]);

        $this->artisan('search:archive-logs --days=90')
            ->expectsOutput('Archiving search logs older than 90 days...')
            ->expectsOutput('Successfully archived 1 search log(s).')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('search_logs', [
            'query' => 'old query',
        ]);

        $this->assertDatabaseHas('search_logs', [
            'query' => 'recent query',
        ]);
    }

    public function test_command_handles_no_logs_to_archive(): void
    {
        SearchLog::factory()->create([
            'query' => 'recent query',
            'user_id' => null,
            'created_at' => now()->subDays(30),
        ]);

        $this->artisan('search:archive-logs --days=90')
            ->expectsOutput('Archiving search logs older than 90 days...')
            ->expectsOutput('No search logs to archive.')
            ->assertExitCode(0);
    }

    public function test_command_uses_default_days_when_not_specified(): void
    {
        SearchLog::factory()->create([
            'query' => 'old query',
            'user_id' => null,
            'created_at' => now()->subDays(100),
        ]);

        $this->artisan('search:archive-logs')
            ->expectsOutput('Archiving search logs older than 90 days...')
            ->assertExitCode(0);
    }

    public function test_command_fails_with_invalid_days(): void
    {
        $this->artisan('search:archive-logs --days=0')
            ->expectsOutput('Days must be a positive integer.')
            ->assertExitCode(1);

        $this->artisan('search:archive-logs --days=-1')
            ->expectsOutput('Days must be a positive integer.')
            ->assertExitCode(1);
    }

    public function test_command_archives_multiple_old_logs(): void
    {
        SearchLog::factory()->count(5)->create([
            'user_id' => null,
            'created_at' => now()->subDays(100),
        ]);

        SearchLog::factory()->count(3)->create([
            'user_id' => null,
            'created_at' => now()->subDays(30),
        ]);

        $this->artisan('search:archive-logs --days=90')
            ->expectsOutput('Successfully archived 5 search log(s).')
            ->assertExitCode(0);

        $this->assertEquals(3, SearchLog::count());
    }

    public function test_command_archives_logs_with_custom_days(): void
    {
        SearchLog::factory()->create([
            'query' => 'query 1',
            'user_id' => null,
            'created_at' => now()->subDays(60),
        ]);

        SearchLog::factory()->create([
            'query' => 'query 2',
            'user_id' => null,
            'created_at' => now()->subDays(30),
        ]);

        $this->artisan('search:archive-logs --days=45')
            ->expectsOutput('Archiving search logs older than 45 days...')
            ->expectsOutput('Successfully archived 1 search log(s).')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('search_logs', [
            'query' => 'query 1',
        ]);

        $this->assertDatabaseHas('search_logs', [
            'query' => 'query 2',
        ]);
    }
}
