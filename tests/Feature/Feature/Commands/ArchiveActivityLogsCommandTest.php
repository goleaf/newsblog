<?php

namespace Tests\Feature\Feature\Commands;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArchiveActivityLogsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_archives_old_activity_logs(): void
    {
        $user = User::factory()->create();

        // Create old logs (100 days old)
        ActivityLog::factory()->count(5)->create([
            'causer_type' => User::class,
            'causer_id' => $user->id,
            'created_at' => now()->subDays(100),
        ]);

        // Create recent logs (30 days old)
        ActivityLog::factory()->count(3)->create([
            'causer_type' => User::class,
            'causer_id' => $user->id,
            'created_at' => now()->subDays(30),
        ]);

        $this->artisan('activity-logs:archive', ['--days' => 90])
            ->expectsOutput('Archiving activity logs older than 90 days...')
            ->expectsOutput('Successfully archived 5 activity log(s).')
            ->assertExitCode(0);

        $this->assertDatabaseCount('activity_logs', 3);
    }

    public function test_handles_no_logs_to_archive(): void
    {
        $user = User::factory()->create();

        ActivityLog::factory()->count(3)->create([
            'causer_type' => User::class,
            'causer_id' => $user->id,
            'created_at' => now()->subDays(30),
        ]);

        $this->artisan('activity-logs:archive', ['--days' => 90])
            ->expectsOutput('No activity logs to archive.')
            ->assertExitCode(0);

        $this->assertDatabaseCount('activity_logs', 3);
    }

    public function test_rejects_invalid_days_parameter(): void
    {
        $this->artisan('activity-logs:archive', ['--days' => 0])
            ->expectsOutput('Days must be a positive integer.')
            ->assertExitCode(1);

        $this->artisan('activity-logs:archive', ['--days' => -5])
            ->expectsOutput('Days must be a positive integer.')
            ->assertExitCode(1);
    }

    public function test_uses_default_days_parameter(): void
    {
        $user = User::factory()->create();

        ActivityLog::factory()->count(2)->create([
            'causer_type' => User::class,
            'causer_id' => $user->id,
            'created_at' => now()->subDays(100),
        ]);

        $this->artisan('activity-logs:archive')
            ->assertExitCode(0);

        $this->assertDatabaseCount('activity_logs', 0);
    }
}
