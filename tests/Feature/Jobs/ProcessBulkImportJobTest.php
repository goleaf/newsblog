<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessBulkImportJob;
use App\Models\Post;
use App\Models\User;
use App\Services\BulkImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcessBulkImportJobTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $testFilePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->testFilePath = database_path('data/test_small.csv');
    }

    public function test_job_executes_import_successfully(): void
    {
        $options = [
            'skip_content' => true,
            'skip_images' => true,
            'chunk_size' => 100,
        ];

        $job = new ProcessBulkImportJob($this->testFilePath, $options, $this->user->id);
        $job->handle(app(BulkImportService::class));

        $this->assertGreaterThan(0, Post::count());
    }

    public function test_job_updates_progress_cache_during_import(): void
    {
        $options = [
            'skip_content' => true,
            'skip_images' => true,
            'chunk_size' => 10,
        ];

        $job = new ProcessBulkImportJob($this->testFilePath, $options, $this->user->id);
        $jobId = md5($this->testFilePath.json_encode($options));

        $job->handle(app(BulkImportService::class));

        $progressData = Cache::get("import_job_{$jobId}");

        $this->assertNotNull($progressData);
        $this->assertEquals('completed', $progressData['status']);
        $this->assertArrayHasKey('current', $progressData);
        $this->assertArrayHasKey('total', $progressData);
        $this->assertArrayHasKey('percentage', $progressData);
        $this->assertEquals(100, $progressData['percentage']);
    }

    public function test_job_stores_start_time_in_cache(): void
    {
        $options = [
            'skip_content' => true,
            'skip_images' => true,
        ];

        $job = new ProcessBulkImportJob($this->testFilePath, $options, $this->user->id);
        $jobId = md5($this->testFilePath.json_encode($options));

        $job->handle(app(BulkImportService::class));

        $startTime = Cache::get("import_job_{$jobId}_start_time");
        $this->assertNotNull($startTime);
    }

    public function test_job_removes_from_registry_on_completion(): void
    {
        $options = [
            'skip_content' => true,
            'skip_images' => true,
        ];

        $job = new ProcessBulkImportJob($this->testFilePath, $options, $this->user->id);
        $jobId = md5($this->testFilePath.json_encode($options));

        // Clear registry first
        Cache::forget('import_jobs_registry');

        $job->handle(app(BulkImportService::class));

        $registry = Cache::get('import_jobs_registry', []);
        $this->assertNotContains($jobId, $registry);
    }

    public function test_job_handles_import_failure_gracefully(): void
    {
        $invalidFilePath = 'nonexistent.csv';
        $options = [];

        $job = new ProcessBulkImportJob($invalidFilePath, $options, $this->user->id);

        $this->expectException(\Exception::class);

        $job->handle(app(BulkImportService::class));
    }

    public function test_job_updates_progress_with_failed_status_on_error(): void
    {
        $invalidFilePath = 'nonexistent.csv';
        $options = [];
        $jobId = md5($invalidFilePath.json_encode($options));

        $job = new ProcessBulkImportJob($invalidFilePath, $options, $this->user->id);

        try {
            $job->handle(app(BulkImportService::class));
        } catch (\Exception $e) {
            // Expected
        }

        $progressData = Cache::get("import_job_{$jobId}");
        $this->assertNotNull($progressData);
        $this->assertEquals('failed', $progressData['status']);
        $this->assertArrayHasKey('error', $progressData);
    }

    public function test_job_failed_method_updates_cache_with_permanent_failure(): void
    {
        $options = [
            'skip_content' => true,
            'skip_images' => true,
        ];
        $jobId = md5($this->testFilePath.json_encode($options));

        $job = new ProcessBulkImportJob($this->testFilePath, $options, $this->user->id);
        $exception = new \Exception('Test failure');

        $job->failed($exception);

        $progressData = Cache::get("import_job_{$jobId}");
        $this->assertNotNull($progressData);
        $this->assertEquals('failed', $progressData['status']);
        $this->assertTrue($progressData['permanent_failure']);
    }

    public function test_job_stores_final_import_results_in_cache(): void
    {
        $options = [
            'skip_content' => true,
            'skip_images' => true,
        ];

        $job = new ProcessBulkImportJob($this->testFilePath, $options, $this->user->id);
        $jobId = md5($this->testFilePath.json_encode($options));

        $job->handle(app(BulkImportService::class));

        $progressData = Cache::get("import_job_{$jobId}");

        $this->assertNotNull($progressData);
        $this->assertArrayHasKey('posts_created', $progressData);
        $this->assertArrayHasKey('successful', $progressData);
        $this->assertArrayHasKey('total_rows', $progressData);
    }

    public function test_job_has_correct_retry_configuration(): void
    {
        $job = new ProcessBulkImportJob($this->testFilePath, [], $this->user->id);

        $this->assertEquals(3, $job->tries);
        $this->assertEquals(60, $job->backoff);
        $this->assertEquals(3600, $job->timeout);
    }

    public function test_job_includes_file_basename_in_progress_data(): void
    {
        $options = [
            'skip_content' => true,
            'skip_images' => true,
        ];

        $job = new ProcessBulkImportJob($this->testFilePath, $options, $this->user->id);
        $jobId = md5($this->testFilePath.json_encode($options));

        $job->handle(app(BulkImportService::class));

        $progressData = Cache::get("import_job_{$jobId}");

        $this->assertNotNull($progressData);
        $this->assertEquals(basename($this->testFilePath), $progressData['file']);
    }

    public function test_job_includes_timestamp_in_progress_data(): void
    {
        $options = [
            'skip_content' => true,
            'skip_images' => true,
        ];

        $job = new ProcessBulkImportJob($this->testFilePath, $options, $this->user->id);
        $jobId = md5($this->testFilePath.json_encode($options));

        $job->handle(app(BulkImportService::class));

        $progressData = Cache::get("import_job_{$jobId}");

        $this->assertNotNull($progressData);
        $this->assertArrayHasKey('updated_at', $progressData);
    }

    public function test_job_dispatches_to_queue_when_threshold_exceeded(): void
    {
        Queue::fake();

        $filePath = database_path('data/test_small.csv');

        $this->artisan('news:import', [
            'file' => $filePath,
            '--queue' => true,
            '--user-id' => $this->user->id,
        ])
            ->assertExitCode(0);

        Queue::assertPushed(ProcessBulkImportJob::class, function ($job) use ($filePath) {
            return $job->filePath === $filePath && $job->userId === $this->user->id;
        });
    }
}
