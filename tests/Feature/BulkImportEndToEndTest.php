<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * End-to-End Integration Tests for Bulk News Importer
 *
 * Note: These tests call the BulkImportService directly rather than through
 * the Artisan command because the service uses explicit DB::commit() calls
 * which conflict with Laravel's RefreshDatabase trait that wraps tests in
 * transactions. Direct service calls allow the commits to persist within
 * the test's transaction scope.
 */
class BulkImportEndToEndTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_imports_actual_csv_file_and_displays_correct_summary(): void
    {
        $filePath = database_path('data/test_small.csv');

        $this->artisan('news:import', [
            'file' => $filePath,
            '--skip-content' => true,
            '--skip-images' => true,
            '--user-id' => $this->user->id,
        ])
            ->expectsOutput('Starting import...')
            ->expectsOutput('Import Summary')
            ->expectsOutputToContain('Posts Created:')
            ->expectsOutputToContain('Tags Created:')
            ->expectsOutputToContain('Categories Created:')
            ->assertExitCode(0);
    }

    public function test_verifies_posts_created_correctly_with_all_fields(): void
    {
        $service = app(\App\Services\BulkImportService::class);
        $filePath = database_path('data/test_small.csv');

        $result = $service->import($filePath, [
            'status' => 'published',
            'user_id' => $this->user->id,
            'skip_content' => true,
            'skip_images' => true,
        ]);

        $this->assertGreaterThan(0, $result['posts_created']);

        $post = Post::first();

        $this->assertNotNull($post);
        $this->assertNotEmpty($post->title);
        $this->assertNotEmpty($post->slug);
        $this->assertEquals($this->user->id, $post->user_id);
        $this->assertEquals('published', $post->status);
        $this->assertNotNull($post->published_at);
        $this->assertNotNull($post->category_id);
        $this->assertInstanceOf(\DateTime::class, $post->created_at);
        $this->assertInstanceOf(\DateTime::class, $post->updated_at);
    }

    public function test_verifies_tags_created_correctly_with_slugs(): void
    {
        $service = app(\App\Services\BulkImportService::class);
        $filePath = database_path('data/test_small.csv');

        $result = $service->import($filePath, [
            'user_id' => $this->user->id,
            'skip_content' => true,
            'skip_images' => true,
        ]);

        $this->assertGreaterThan(0, $result['tags_created']);

        $tags = Tag::all();

        $this->assertGreaterThan(0, $tags->count());

        foreach ($tags as $tag) {
            $this->assertNotEmpty($tag->name);
            $this->assertNotEmpty($tag->slug);
            $this->assertMatchesRegularExpression('/^[a-z0-9\-]+$/', $tag->slug, 'Tag slug is not URL-friendly');
        }
    }

    public function test_verifies_categories_created_correctly_with_slugs(): void
    {
        $service = app(\App\Services\BulkImportService::class);
        $filePath = database_path('data/test_small.csv');

        $result = $service->import($filePath, [
            'user_id' => $this->user->id,
            'skip_content' => true,
            'skip_images' => true,
        ]);

        $this->assertGreaterThan(0, $result['categories_created']);

        $categories = Category::all();

        $this->assertGreaterThan(0, $categories->count());

        foreach ($categories as $category) {
            $this->assertNotEmpty($category->name);
            $this->assertNotEmpty($category->slug);
            $this->assertMatchesRegularExpression('/^[a-z0-9\-]+$/', $category->slug, 'Category slug is not URL-friendly');
        }
    }

    public function test_verifies_relationships_established_in_pivot_table(): void
    {
        $service = app(\App\Services\BulkImportService::class);
        $filePath = database_path('data/test_small.csv');

        $result = $service->import($filePath, [
            'user_id' => $this->user->id,
            'skip_content' => true,
            'skip_images' => true,
        ]);

        $this->assertGreaterThan(0, $result['posts_created']);

        $pivotCount = DB::table('post_tag')->count();
        $this->assertGreaterThan(0, $pivotCount, 'No post-tag relationships were created');

        $post = Post::with('tags')->first();
        $this->assertGreaterThan(0, $post->tags->count(), 'Post has no associated tags');

        foreach ($post->tags as $tag) {
            $this->assertInstanceOf(Tag::class, $tag);
            $this->assertNotEmpty($tag->name);
        }
    }

    public function test_verifies_content_assigned_when_generation_enabled(): void
    {
        $service = app(\App\Services\BulkImportService::class);
        $filePath = database_path('data/test_small.csv');

        $result = $service->import($filePath, [
            'user_id' => $this->user->id,
            'skip_images' => true,
        ]);

        $this->assertGreaterThan(0, $result['content_generated']);

        $posts = Post::all();

        foreach ($posts as $post) {
            $this->assertNotEmpty($post->content, "Post '{$post->title}' has no content");
            $this->assertNotEmpty($post->excerpt, "Post '{$post->title}' has no excerpt");
            $this->assertGreaterThan(0, $post->reading_time, "Post '{$post->title}' has no reading time");

            $wordCount = str_word_count(strip_tags($post->content));
            $this->assertGreaterThanOrEqual(400, $wordCount, "Post content is too short");
        }
    }

    public function test_verifies_images_assigned_when_generation_enabled(): void
    {
        $service = app(\App\Services\BulkImportService::class);
        $filePath = database_path('data/test_small.csv');

        $result = $service->import($filePath, [
            'user_id' => $this->user->id,
            'skip_content' => true,
        ]);

        $this->assertGreaterThan(0, $result['images_assigned']);

        $posts = Post::all();

        foreach ($posts as $post) {
            $this->assertNotEmpty($post->featured_image, "Post '{$post->title}' has no featured image");
            $this->assertNotEmpty($post->image_alt_text, "Post '{$post->title}' has no image alt text");
        }
    }

    public function test_measures_import_speed_for_small_dataset(): void
    {
        $service = app(\App\Services\BulkImportService::class);
        $filePath = database_path('data/test_small.csv');

        $startTime = microtime(true);

        $result = $service->import($filePath, [
            'user_id' => $this->user->id,
            'skip_content' => true,
            'skip_images' => true,
        ]);

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $postCount = $result['posts_created'];
        $postsPerSecond = $postCount / $duration;

        $this->assertGreaterThan(0, $postsPerSecond);
        $this->assertGreaterThan(10, $postsPerSecond, 'Import speed is too slow (less than 10 posts/second)');

        echo "\n";
        echo "Import Performance Metrics:\n";
        echo "- Posts imported: {$postCount}\n";
        echo "- Duration: ".round($duration, 2)." seconds\n";
        echo "- Speed: ".round($postsPerSecond, 2)." posts/second\n";
    }

    public function test_measures_memory_usage_during_import(): void
    {
        $service = app(\App\Services\BulkImportService::class);
        $filePath = database_path('data/test_small.csv');

        $startMemory = memory_get_usage(true);

        $result = $service->import($filePath, [
            'user_id' => $this->user->id,
            'skip_content' => true,
            'skip_images' => true,
        ]);

        $peakMemory = memory_get_peak_usage(true);
        $memoryUsed = $peakMemory - $startMemory;
        $memoryUsedMB = round($memoryUsed / 1024 / 1024, 2);

        $postCount = $result['posts_created'];
        $memoryPerPost = $memoryUsed / $postCount;
        $memoryPerPostKB = round($memoryPerPost / 1024, 2);

        $this->assertLessThan(50 * 1024 * 1024, $memoryUsed, 'Memory usage exceeded 50MB for small dataset');

        echo "\n";
        echo "Memory Usage Metrics:\n";
        echo "- Total memory used: {$memoryUsedMB} MB\n";
        echo "- Memory per post: {$memoryPerPostKB} KB\n";
        echo "- Peak memory: ".round($peakMemory / 1024 / 1024, 2)." MB\n";
    }

    public function test_end_to_end_import_with_all_features_enabled(): void
    {
        $service = app(\App\Services\BulkImportService::class);
        $filePath = database_path('data/test_small.csv');

        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        $result = $service->import($filePath, [
            'status' => 'published',
            'user_id' => $this->user->id,
        ]);

        $endTime = microtime(true);
        $peakMemory = memory_get_peak_usage(true);

        $posts = Post::with(['tags', 'category'])->get();
        $postCount = $posts->count();

        $this->assertGreaterThan(0, $postCount);
        $this->assertEquals($result['posts_created'], $postCount);

        foreach ($posts as $post) {
            $this->assertNotEmpty($post->title);
            $this->assertNotEmpty($post->slug);
            $this->assertNotEmpty($post->content);
            $this->assertNotEmpty($post->excerpt);
            $this->assertNotEmpty($post->featured_image);
            $this->assertNotEmpty($post->image_alt_text);
            $this->assertGreaterThan(0, $post->reading_time);
            $this->assertEquals($this->user->id, $post->user_id);
            $this->assertEquals('published', $post->status);
            $this->assertNotNull($post->published_at);
            $this->assertNotNull($post->category_id);
            $this->assertInstanceOf(Category::class, $post->category);
            $this->assertGreaterThan(0, $post->tags->count());
        }

        $tagCount = Tag::count();
        $categoryCount = Category::count();
        $pivotCount = DB::table('post_tag')->count();

        $this->assertGreaterThan(0, $tagCount);
        $this->assertGreaterThan(0, $categoryCount);
        $this->assertGreaterThan(0, $pivotCount);

        $duration = $endTime - $startTime;
        $memoryUsed = ($peakMemory - $startMemory) / 1024 / 1024;

        echo "\n";
        echo "=== End-to-End Import Test Results ===\n";
        echo "Posts created: {$postCount}\n";
        echo "Tags created: {$tagCount}\n";
        echo "Categories created: {$categoryCount}\n";
        echo "Relationships created: {$pivotCount}\n";
        echo "Duration: ".round($duration, 2)." seconds\n";
        echo "Speed: ".round($postCount / $duration, 2)." posts/second\n";
        echo "Memory used: ".round($memoryUsed, 2)." MB\n";
        echo "======================================\n";
    }

    public function test_verifies_no_duplicate_posts_created(): void
    {
        $service = app(\App\Services\BulkImportService::class);
        $filePath = database_path('data/test_small.csv');

        $result1 = $service->import($filePath, [
            'user_id' => $this->user->id,
            'skip_content' => true,
            'skip_images' => true,
        ]);

        $firstImportCount = Post::count();

        $result2 = $service->import($filePath, [
            'user_id' => $this->user->id,
            'skip_content' => true,
            'skip_images' => true,
        ]);

        $secondImportCount = Post::count();

        $this->assertEquals($firstImportCount, $secondImportCount, 'Duplicate posts were created on second import');
        $this->assertGreaterThan(0, $result2['skipped'], 'No posts were skipped on second import');
    }

    public function test_verifies_slug_uniqueness_across_posts(): void
    {
        $service = app(\App\Services\BulkImportService::class);
        $filePath = database_path('data/test_small.csv');

        $service->import($filePath, [
            'user_id' => $this->user->id,
            'skip_content' => true,
            'skip_images' => true,
        ]);

        $slugs = Post::pluck('slug')->toArray();
        $uniqueSlugs = array_unique($slugs);

        $this->assertEquals(count($slugs), count($uniqueSlugs), 'Duplicate slugs were found');
    }

    public function test_verifies_category_assignment_is_correct(): void
    {
        $service = app(\App\Services\BulkImportService::class);
        $filePath = database_path('data/test_small.csv');

        $service->import($filePath, [
            'user_id' => $this->user->id,
            'skip_content' => true,
            'skip_images' => true,
        ]);

        $posts = Post::with('category')->get();

        foreach ($posts as $post) {
            $this->assertNotNull($post->category_id, "Post '{$post->title}' has no category assigned");
            $this->assertInstanceOf(Category::class, $post->category, "Post '{$post->title}' category relationship is broken");
            $this->assertNotEmpty($post->category->name);
        }
    }

    public function test_verifies_tag_relationships_are_bidirectional(): void
    {
        $service = app(\App\Services\BulkImportService::class);
        $filePath = database_path('data/test_small.csv');

        $service->import($filePath, [
            'user_id' => $this->user->id,
            'skip_content' => true,
            'skip_images' => true,
        ]);

        $post = Post::with('tags')->first();
        $tag = $post->tags->first();

        $this->assertNotNull($tag);

        $tagPosts = $tag->posts;
        $this->assertGreaterThan(0, $tagPosts->count());
        $this->assertTrue($tagPosts->contains($post->id));
    }

    public function test_verifies_timestamps_are_set_correctly(): void
    {
        $service = app(\App\Services\BulkImportService::class);
        $filePath = database_path('data/test_small.csv');

        $beforeImport = now();

        $service->import($filePath, [
            'user_id' => $this->user->id,
            'skip_content' => true,
            'skip_images' => true,
        ]);

        $afterImport = now();

        $posts = Post::all();

        foreach ($posts as $post) {
            $this->assertNotNull($post->created_at);
            $this->assertNotNull($post->updated_at);
            $this->assertGreaterThanOrEqual($beforeImport->timestamp, $post->created_at->timestamp);
            $this->assertLessThanOrEqual($afterImport->timestamp, $post->created_at->timestamp);
        }
    }

    public function test_verifies_published_at_set_for_published_status(): void
    {
        $service = app(\App\Services\BulkImportService::class);
        $filePath = database_path('data/test_small.csv');

        $service->import($filePath, [
            'status' => 'published',
            'user_id' => $this->user->id,
            'skip_content' => true,
            'skip_images' => true,
        ]);

        $posts = Post::where('status', 'published')->get();

        foreach ($posts as $post) {
            $this->assertNotNull($post->published_at, "Published post '{$post->title}' has no published_at timestamp");
        }
    }

    public function test_verifies_published_at_null_for_draft_status(): void
    {
        $service = app(\App\Services\BulkImportService::class);
        $filePath = database_path('data/test_small.csv');

        $service->import($filePath, [
            'status' => 'draft',
            'user_id' => $this->user->id,
            'skip_content' => true,
            'skip_images' => true,
        ]);

        $posts = Post::where('status', 'draft')->get();

        foreach ($posts as $post) {
            $this->assertNull($post->published_at, "Draft post '{$post->title}' should not have published_at timestamp");
        }
    }

    public function test_import_handles_large_dataset_efficiently(): void
    {
        $mediumFile = database_path('data/test_medium.csv');

        if (! file_exists($mediumFile)) {
            $this->markTestSkipped('test_medium.csv file not found');
        }

        $service = app(\App\Services\BulkImportService::class);

        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        $result = $service->import($mediumFile, [
            'user_id' => $this->user->id,
            'skip_content' => true,
            'skip_images' => true,
            'chunk_size' => 1000,
        ]);

        $endTime = microtime(true);
        $peakMemory = memory_get_peak_usage(true);

        $postCount = $result['posts_created'];
        $duration = $endTime - $startTime;
        $memoryUsed = ($peakMemory - $startMemory) / 1024 / 1024;
        $postsPerSecond = $postCount / $duration;

        $this->assertGreaterThan(0, $postCount);
        $this->assertGreaterThan(50, $postsPerSecond, 'Import speed is too slow for large dataset');
        $this->assertLessThan(256, $memoryUsed, 'Memory usage exceeded 256MB for medium dataset');

        echo "\n";
        echo "=== Large Dataset Import Performance ===\n";
        echo "Posts imported: {$postCount}\n";
        echo "Duration: ".round($duration, 2)." seconds\n";
        echo "Speed: ".round($postsPerSecond, 2)." posts/second\n";
        echo "Memory used: ".round($memoryUsed, 2)." MB\n";
        echo "========================================\n";
    }
}
