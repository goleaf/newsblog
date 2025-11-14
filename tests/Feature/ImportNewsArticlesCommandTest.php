<?php

namespace Tests\Feature;

use App\Jobs\ProcessBulkImportJob;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ImportNewsArticlesCommandTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_imports_posts_from_small_csv_file(): void
    {
        $filePath = database_path('data/test_small.csv');

        $result = $this->artisan('news:import', [
            'file' => $filePath,
            '--skip-content' => true,
            '--skip-images' => true,
            '--user-id' => $this->user->id,
        ]);

        $result->expectsOutput('Starting import...')
            ->assertExitCode(0);

        $this->assertGreaterThan(0, Post::count(), 'No posts were created');
        $this->assertGreaterThan(0, Tag::count(), 'No tags were created');
        $this->assertGreaterThan(0, Category::count(), 'No categories were created');
    }

    public function test_displays_import_summary(): void
    {
        $filePath = database_path('data/test_small.csv');

        $this->artisan('news:import', [
            'file' => $filePath,
            '--skip-content' => true,
            '--skip-images' => true,
            '--user-id' => $this->user->id,
        ])
            ->expectsOutput('Import Summary')
            ->assertExitCode(0);
    }

    public function test_handles_skip_content_option(): void
    {
        $filePath = database_path('data/test_small.csv');

        $this->artisan('news:import', [
            'file' => $filePath,
            '--skip-content' => true,
            '--skip-images' => true,
            '--user-id' => $this->user->id,
        ])
            ->assertExitCode(0);

        $post = Post::first();
        $this->assertEmpty($post->content);
        $this->assertEmpty($post->excerpt);
    }

    public function test_generates_content_when_skip_content_not_provided(): void
    {
        $filePath = database_path('data/test_small.csv');

        $this->artisan('news:import', [
            'file' => $filePath,
            '--skip-images' => true,
            '--user-id' => $this->user->id,
        ])
            ->assertExitCode(0);

        $post = Post::first();
        $this->assertNotEmpty($post->content);
        $this->assertNotEmpty($post->excerpt);
        $this->assertGreaterThan(0, $post->reading_time);
    }

    public function test_handles_skip_images_option(): void
    {
        $filePath = database_path('data/test_small.csv');

        $this->artisan('news:import', [
            'file' => $filePath,
            '--skip-content' => true,
            '--skip-images' => true,
            '--user-id' => $this->user->id,
        ])
            ->assertExitCode(0);

        $post = Post::first();
        $this->assertNull($post->featured_image);
        $this->assertNull($post->image_alt_text);
    }

    public function test_assigns_images_when_skip_images_not_provided(): void
    {
        $filePath = database_path('data/test_small.csv');

        $this->artisan('news:import', [
            'file' => $filePath,
            '--skip-content' => true,
            '--user-id' => $this->user->id,
        ])
            ->assertExitCode(0);

        $post = Post::first();
        $this->assertNotEmpty($post->featured_image);
        $this->assertNotEmpty($post->image_alt_text);
    }

    public function test_handles_status_option_draft(): void
    {
        $filePath = database_path('data/test_small.csv');

        $this->artisan('news:import', [
            'file' => $filePath,
            '--status' => 'draft',
            '--skip-content' => true,
            '--skip-images' => true,
            '--user-id' => $this->user->id,
        ])
            ->assertExitCode(0);

        $post = Post::first();
        $this->assertEquals('draft', $post->status);
        $this->assertNull($post->published_at);
    }

    public function test_handles_status_option_published(): void
    {
        $filePath = database_path('data/test_small.csv');

        $this->artisan('news:import', [
            'file' => $filePath,
            '--status' => 'published',
            '--skip-content' => true,
            '--skip-images' => true,
            '--user-id' => $this->user->id,
        ])
            ->assertExitCode(0);

        $post = Post::first();
        $this->assertEquals('published', $post->status);
        $this->assertNotNull($post->published_at);
    }

    public function test_handles_limit_option(): void
    {
        $filePath = database_path('data/test_small.csv');

        $limit = 5;

        $this->artisan('news:import', [
            'file' => $filePath,
            '--limit' => $limit,
            '--skip-content' => true,
            '--skip-images' => true,
            '--user-id' => $this->user->id,
        ])->assertExitCode(0);

        $this->assertEquals($limit, \App\Models\Post::count());
    }

    public function test_validates_invalid_limit(): void
    {
        $filePath = database_path('data/test_small.csv');

        $this->artisan('news:import', [
            'file' => $filePath,
            '--limit' => -1,
        ])
            ->expectsOutput('Limit must be a positive integer')
            ->assertExitCode(1);
    }

    public function test_fresh_option_resets_database_before_import(): void
    {
        // Create an existing post that should be cleared
        \App\Models\Post::factory()->create();

        $this->assertGreaterThan(0, \App\Models\Post::count());

        $filePath = database_path('data/test_small.csv');

        $this->artisan('news:import', [
            'file' => $filePath,
            '--fresh' => true,
            '--limit' => 3,
            '--skip-content' => true,
            '--skip-images' => true,
            '--user-id' => $this->user->id,
        ])->assertExitCode(0);

        // Should only have the newly imported posts (3), previous data cleared
        $this->assertEquals(3, \App\Models\Post::count());
    }

    public function test_handles_user_id_option(): void
    {
        $specificUser = User::factory()->create();
        $filePath = database_path('data/test_small.csv');

        $this->artisan('news:import', [
            'file' => $filePath,
            '--user-id' => $specificUser->id,
            '--skip-content' => true,
            '--skip-images' => true,
        ])
            ->assertExitCode(0);

        $post = Post::first();
        $this->assertEquals($specificUser->id, $post->user_id);
    }

    public function test_handles_chunk_size_option(): void
    {
        $filePath = database_path('data/test_small.csv');

        $this->artisan('news:import', [
            'file' => $filePath,
            '--chunk-size' => 2,
            '--skip-content' => true,
            '--skip-images' => true,
            '--user-id' => $this->user->id,
        ])
            ->assertExitCode(0);

        $this->assertGreaterThan(0, Post::count());
    }

    public function test_validates_invalid_chunk_size(): void
    {
        $filePath = database_path('data/test_small.csv');

        $this->artisan('news:import', [
            'file' => $filePath,
            '--chunk-size' => -1,
        ])
            ->expectsOutput('Chunk size must be a positive integer')
            ->assertExitCode(1);
    }

    public function test_validates_invalid_status(): void
    {
        $filePath = database_path('data/test_small.csv');

        $this->artisan('news:import', [
            'file' => $filePath,
            '--status' => 'invalid',
        ])
            ->expectsOutput('Status must be one of: draft, published, scheduled')
            ->assertExitCode(1);
    }

    public function test_validates_invalid_user_id(): void
    {
        $filePath = database_path('data/test_small.csv');

        $this->artisan('news:import', [
            'file' => $filePath,
            '--user-id' => -1,
        ])
            ->expectsOutput('User ID must be a positive integer')
            ->assertExitCode(1);
    }

    public function test_handles_nonexistent_file(): void
    {
        $this->artisan('news:import', [
            'file' => 'nonexistent.csv',
        ])
            ->expectsOutput('File or directory not found: nonexistent.csv')
            ->assertExitCode(1);
    }

    public function test_handles_non_csv_file(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        rename($tempFile, $tempFile.'.txt');
        $tempFile = $tempFile.'.txt';
        file_put_contents($tempFile, 'test content');

        try {
            $this->artisan('news:import', [
                'file' => $tempFile,
            ])
                ->expectsOutputToContain('File must be a CSV file')
                ->assertExitCode(1);
        } finally {
            unlink($tempFile);
        }
    }

    public function test_processes_directory_with_multiple_csv_files(): void
    {
        $tempDir = sys_get_temp_dir().'/csv_test_'.uniqid();
        mkdir($tempDir);

        try {
            // Create two CSV files
            file_put_contents($tempDir.'/file1.csv', "title,tags,categories\n\"Test 1\",\"tag1\",\"Category1\"\n");
            file_put_contents($tempDir.'/file2.csv', "title,tags,categories\n\"Test 2\",\"tag2\",\"Category2\"\n");

            $this->artisan('news:import', [
                'file' => $tempDir,
                '--skip-content' => true,
                '--skip-images' => true,
                '--user-id' => $this->user->id,
            ])
                ->expectsOutput('Found 2 CSV file(s) to process')
                ->assertExitCode(0);

            $this->assertEquals(2, Post::count());
        } finally {
            File::deleteDirectory($tempDir);
        }
    }

    public function test_handles_empty_directory(): void
    {
        $tempDir = sys_get_temp_dir().'/csv_test_empty_'.uniqid();
        mkdir($tempDir);

        try {
            $this->artisan('news:import', [
                'file' => $tempDir,
            ])
                ->expectsOutputToContain('No CSV files found in directory')
                ->assertExitCode(1);
        } finally {
            rmdir($tempDir);
        }
    }

    public function test_dispatches_queue_job_when_queue_option_provided(): void
    {
        Queue::fake();

        $filePath = database_path('data/test_small.csv');

        $this->artisan('news:import', [
            'file' => $filePath,
            '--queue' => true,
            '--user-id' => $this->user->id,
        ])
            ->expectsOutput('Import job dispatched to background queue')
            ->assertExitCode(0);

        Queue::assertPushed(ProcessBulkImportJob::class);
    }

    public function test_displays_progress_during_import(): void
    {
        $filePath = database_path('data/test_small.csv');

        $this->artisan('news:import', [
            'file' => $filePath,
            '--skip-content' => true,
            '--skip-images' => true,
            '--user-id' => $this->user->id,
        ])
            ->expectsOutput('Starting import...')
            ->assertExitCode(0);
    }

    public function test_handles_import_errors_gracefully(): void
    {
        // Create a CSV with invalid data
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_test_');
        file_put_contents($tempFile, "title,tags,categories\n\"\",\"tag1\",\"Category1\"\n\"Valid Title\",\"tag2\",\"Category2\"\n");

        try {
            $this->artisan('news:import', [
                'file' => $tempFile,
                '--skip-content' => true,
                '--skip-images' => true,
                '--user-id' => $this->user->id,
            ])
                ->assertExitCode(1); // Should fail due to errors

            // Valid post should still be created
            $this->assertEquals(1, Post::count());
            $post = Post::first();
            $this->assertEquals('Valid Title', $post->title);
        } finally {
            unlink($tempFile);
        }
    }

    public function test_displays_performance_metrics(): void
    {
        $filePath = database_path('data/test_small.csv');

        $this->artisan('news:import', [
            'file' => $filePath,
            '--skip-content' => true,
            '--skip-images' => true,
            '--user-id' => $this->user->id,
        ])
            ->expectsOutputToContain('Duration:')
            ->expectsOutputToContain('Average Speed:')
            ->expectsOutputToContain('Memory Peak:')
            ->assertExitCode(0);
    }

    public function test_displays_posts_created_count(): void
    {
        $filePath = database_path('data/test_small.csv');

        $this->artisan('news:import', [
            'file' => $filePath,
            '--skip-content' => true,
            '--skip-images' => true,
            '--user-id' => $this->user->id,
        ])
            ->expectsOutputToContain('Posts Created:')
            ->expectsOutputToContain('Tags Created:')
            ->expectsOutputToContain('Categories Created:')
            ->assertExitCode(0);
    }

    public function test_uses_default_status_when_not_provided(): void
    {
        $filePath = database_path('data/test_small.csv');

        $this->artisan('news:import', [
            'file' => $filePath,
            '--skip-content' => true,
            '--skip-images' => true,
            '--user-id' => $this->user->id,
        ])
            ->assertExitCode(0);

        $post = Post::first();
        $this->assertEquals('published', $post->status);
    }

    public function test_creates_relationships_between_posts_and_tags(): void
    {
        $filePath = database_path('data/test_small.csv');

        $this->artisan('news:import', [
            'file' => $filePath,
            '--skip-content' => true,
            '--skip-images' => true,
            '--user-id' => $this->user->id,
        ])
            ->assertExitCode(0);

        $post = Post::first();
        $this->assertGreaterThan(0, $post->tags()->count());
    }

    public function test_assigns_category_to_posts(): void
    {
        $filePath = database_path('data/test_small.csv');

        $this->artisan('news:import', [
            'file' => $filePath,
            '--skip-content' => true,
            '--skip-images' => true,
            '--user-id' => $this->user->id,
        ])
            ->assertExitCode(0);

        $post = Post::first();
        $this->assertNotNull($post->category_id);
        $this->assertInstanceOf(Category::class, $post->category);
    }
}
