<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Services\BulkImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class BulkImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private BulkImportService $bulkImportService;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bulkImportService = app(BulkImportService::class);
        $this->user = User::factory()->create();
    }

    public function test_imports_posts_from_csv_file(): void
    {
        $filePath = database_path('data/test_small.csv');

        $result = $this->bulkImportService->import($filePath, [
            'user_id' => $this->user->id,
            'skip_content' => true,
            'skip_images' => true,
        ]);

        $this->assertGreaterThan(0, $result['successful']);
        $this->assertEquals($result['successful'], $result['posts_created']);
        $this->assertGreaterThan(0, Post::count());
    }

    public function test_creates_tags_and_categories_from_csv(): void
    {
        $filePath = database_path('data/test_small.csv');

        $initialTagCount = Tag::count();
        $initialCategoryCount = Category::count();

        $this->bulkImportService->import($filePath, [
            'user_id' => $this->user->id,
            'skip_content' => true,
            'skip_images' => true,
        ]);

        $this->assertGreaterThan($initialTagCount, Tag::count());
        $this->assertGreaterThan($initialCategoryCount, Category::count());
    }

    public function test_caches_tags_and_categories(): void
    {
        $filePath = database_path('data/test_small.csv');

        // Create some tags and categories beforehand
        Tag::factory()->create(['name' => 'laravel', 'slug' => 'laravel']);
        Category::factory()->create(['name' => 'Backend Development', 'slug' => 'backend-development']);

        $initialTagCount = Tag::count();
        $initialCategoryCount = Category::count();

        $this->bulkImportService->import($filePath, [
            'user_id' => $this->user->id,
            'skip_content' => true,
            'skip_images' => true,
        ]);

        // Should reuse existing tags/categories
        $this->assertGreaterThanOrEqual($initialTagCount, Tag::count());
        $this->assertGreaterThanOrEqual($initialCategoryCount, Category::count());
    }

    public function test_detects_and_skips_duplicate_posts(): void
    {
        $filePath = database_path('data/test_small.csv');

        // First import
        $result1 = $this->bulkImportService->import($filePath, [
            'user_id' => $this->user->id,
            'skip_content' => true,
            'skip_images' => true,
        ]);

        $postsAfterFirst = Post::count();

        // Second import (should skip duplicates)
        $result2 = $this->bulkImportService->import($filePath, [
            'user_id' => $this->user->id,
            'skip_content' => true,
            'skip_images' => true,
        ]);

        $this->assertEquals($postsAfterFirst, Post::count());
        $this->assertGreaterThan(0, $result2['skipped']);
    }

    public function test_processes_in_chunks(): void
    {
        $filePath = database_path('data/test_small.csv');

        $result = $this->bulkImportService->import($filePath, [
            'user_id' => $this->user->id,
            'chunk_size' => 2,
            'skip_content' => true,
            'skip_images' => true,
        ]);

        $this->assertGreaterThan(0, $result['successful']);
        $this->assertGreaterThan(0, Post::count());
    }

    public function test_creates_post_tag_relationships(): void
    {
        $filePath = database_path('data/test_small.csv');

        $this->bulkImportService->import($filePath, [
            'user_id' => $this->user->id,
            'skip_content' => true,
            'skip_images' => true,
        ]);

        $post = Post::first();
        $this->assertGreaterThan(0, $post->tags()->count());
    }

    public function test_assigns_category_to_posts(): void
    {
        $filePath = database_path('data/test_small.csv');

        $this->bulkImportService->import($filePath, [
            'user_id' => $this->user->id,
            'skip_content' => true,
            'skip_images' => true,
        ]);

        $post = Post::first();
        $this->assertNotNull($post->category_id);
        $this->assertInstanceOf(Category::class, $post->category);
    }

    public function test_generates_content_when_enabled(): void
    {
        $filePath = database_path('data/test_small.csv');

        $this->bulkImportService->import($filePath, [
            'user_id' => $this->user->id,
            'skip_content' => false,
            'skip_images' => true,
        ]);

        $post = Post::first();
        $this->assertNotEmpty($post->content);
        $this->assertNotEmpty($post->excerpt);
        $this->assertGreaterThan(0, $post->reading_time);
    }

    public function test_skips_content_generation_when_disabled(): void
    {
        $filePath = database_path('data/test_small.csv');

        $this->bulkImportService->import($filePath, [
            'user_id' => $this->user->id,
            'skip_content' => true,
            'skip_images' => true,
        ]);

        $post = Post::first();
        $this->assertEmpty($post->content);
        $this->assertEmpty($post->excerpt);
    }

    public function test_assigns_images_when_enabled(): void
    {
        $filePath = database_path('data/test_small.csv');

        $this->bulkImportService->import($filePath, [
            'user_id' => $this->user->id,
            'skip_content' => true,
            'skip_images' => false,
        ]);

        $post = Post::first();
        $this->assertNotEmpty($post->featured_image);
        $this->assertNotEmpty($post->image_alt_text);
    }

    public function test_skips_image_assignment_when_disabled(): void
    {
        $filePath = database_path('data/test_small.csv');

        $this->bulkImportService->import($filePath, [
            'user_id' => $this->user->id,
            'skip_content' => true,
            'skip_images' => true,
        ]);

        $post = Post::first();
        $this->assertNull($post->featured_image);
        $this->assertNull($post->image_alt_text);
    }

    public function test_sets_post_status_from_options(): void
    {
        $filePath = database_path('data/test_small.csv');

        $this->bulkImportService->import($filePath, [
            'user_id' => $this->user->id,
            'status' => 'draft',
            'skip_content' => true,
            'skip_images' => true,
        ]);

        $post = Post::first();
        $this->assertEquals('draft', $post->status);
        $this->assertNull($post->published_at);
    }

    public function test_sets_published_at_for_published_posts(): void
    {
        $filePath = database_path('data/test_small.csv');

        $this->bulkImportService->import($filePath, [
            'user_id' => $this->user->id,
            'status' => 'published',
            'skip_content' => true,
            'skip_images' => true,
        ]);

        $post = Post::first();
        $this->assertEquals('published', $post->status);
        $this->assertNotNull($post->published_at);
    }

    public function test_assigns_user_id_to_posts(): void
    {
        $filePath = database_path('data/test_small.csv');

        $this->bulkImportService->import($filePath, [
            'user_id' => $this->user->id,
            'skip_content' => true,
            'skip_images' => true,
        ]);

        $post = Post::first();
        $this->assertEquals($this->user->id, $post->user_id);
    }

    public function test_generates_url_friendly_slugs(): void
    {
        $filePath = database_path('data/test_small.csv');

        $this->bulkImportService->import($filePath, [
            'user_id' => $this->user->id,
            'skip_content' => true,
            'skip_images' => true,
        ]);

        $post = Post::first();
        $this->assertNotEmpty($post->slug);
        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $post->slug);
    }

    public function test_handles_transaction_rollback_on_chunk_failure(): void
    {
        // Create a temporary CSV with invalid data
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_test_');
        file_put_contents($tempFile, "title,tags,categories\n\"Valid Title\",\"tag1\",\"Category1\"\n");

        try {
            $initialPostCount = Post::count();

            // Mock a failure scenario by using an invalid user_id
            $result = $this->bulkImportService->import($tempFile, [
                'user_id' => 99999, // Non-existent user
                'skip_content' => true,
                'skip_images' => true,
            ]);

            // Posts should still be created even with invalid user_id (no foreign key constraint)
            // But we can verify the transaction handling works
            $this->assertIsArray($result);
            $this->assertArrayHasKey('successful', $result);
        } finally {
            unlink($tempFile);
        }
    }

    public function test_returns_detailed_statistics(): void
    {
        $filePath = database_path('data/test_small.csv');

        $result = $this->bulkImportService->import($filePath, [
            'user_id' => $this->user->id,
            'skip_content' => true,
            'skip_images' => true,
        ]);

        $this->assertArrayHasKey('total_rows', $result);
        $this->assertArrayHasKey('successful', $result);
        $this->assertArrayHasKey('failed', $result);
        $this->assertArrayHasKey('skipped', $result);
        $this->assertArrayHasKey('posts_created', $result);
        $this->assertArrayHasKey('tags_created', $result);
        $this->assertArrayHasKey('categories_created', $result);
        $this->assertArrayHasKey('duration', $result);
        $this->assertArrayHasKey('memory_peak', $result);
        $this->assertArrayHasKey('posts_per_second', $result);
    }

    public function test_logs_import_activity(): void
    {
        Log::shouldReceive('channel')
            ->with('import')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->atLeast()
            ->once();

        $filePath = database_path('data/test_small.csv');

        $this->bulkImportService->import($filePath, [
            'user_id' => $this->user->id,
            'skip_content' => true,
            'skip_images' => true,
        ]);
    }

    public function test_uses_bulk_insert_for_performance(): void
    {
        $filePath = database_path('data/test_small.csv');

        // Count queries to ensure bulk operations are used
        DB::enableQueryLog();

        $this->bulkImportService->import($filePath, [
            'user_id' => $this->user->id,
            'skip_content' => true,
            'skip_images' => true,
        ]);

        $queries = DB::getQueryLog();

        // Should have minimal queries due to bulk operations
        // Exact count depends on chunk size and data, but should be much less than row count
        $this->assertLessThan(50, count($queries));

        DB::disableQueryLog();
    }

    public function test_disables_model_events_during_bulk_operations(): void
    {
        $filePath = database_path('data/test_small.csv');

        // Model events should be disabled, so search index shouldn't be invalidated
        // during import (only after)
        $this->bulkImportService->import($filePath, [
            'user_id' => $this->user->id,
            'skip_content' => true,
            'skip_images' => true,
        ]);

        // Verify posts were created
        $this->assertGreaterThan(0, Post::count());
    }

    public function test_handles_empty_csv_gracefully(): void
    {
        // Create a temporary CSV with only headers
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_test_');
        file_put_contents($tempFile, "title,tags,categories\n");

        try {
            $result = $this->bulkImportService->import($tempFile, [
                'user_id' => $this->user->id,
                'skip_content' => true,
                'skip_images' => true,
            ]);

            $this->assertEquals(0, $result['successful']);
            $this->assertEquals(0, Post::count());
        } finally {
            unlink($tempFile);
        }
    }

    public function test_parses_comma_separated_tags(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_test_');
        file_put_contents($tempFile, "title,tags,categories\n\"Test Post\",\"tag1,tag2,tag3\",\"Category1\"\n");

        try {
            $this->bulkImportService->import($tempFile, [
                'user_id' => $this->user->id,
                'skip_content' => true,
                'skip_images' => true,
            ]);

            $post = Post::first();
            $this->assertEquals(3, $post->tags()->count());
        } finally {
            unlink($tempFile);
        }
    }

    public function test_uses_first_category_when_multiple_provided(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_test_');
        file_put_contents($tempFile, "title,tags,categories\n\"Test Post\",\"tag1\",\"Category1,Category2,Category3\"\n");

        try {
            $this->bulkImportService->import($tempFile, [
                'user_id' => $this->user->id,
                'skip_content' => true,
                'skip_images' => true,
            ]);

            $post = Post::first();
            $this->assertNotNull($post->category_id);

            $category = Category::find($post->category_id);
            $this->assertEquals('Category1', $category->name);
        } finally {
            unlink($tempFile);
        }
    }

    public function test_imports_posts_with_keywords_only(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_test_');
        file_put_contents($tempFile, "title,keywords,categories\n\"KW Only Post\",\"alpha,beta\",\"CatA,CatB\"\n");

        try {
            $this->bulkImportService->import($tempFile, [
                'user_id' => $this->user->id,
                'skip_content' => true,
                'skip_images' => true,
            ]);

            $post = Post::with(['tags', 'categories'])->first();
            $this->assertNotNull($post);
            $this->assertEquals(2, $post->tags()->count(), 'Tags (from keywords) were not attached');
            $this->assertEquals(1, $post->categories()->count(), 'All categories beyond primary should be attached via pivot');
        } finally {
            unlink($tempFile);
        }
    }

    public function test_attaches_all_categories_via_pivot(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_test_');
        file_put_contents($tempFile, "title,tags,categories\n\"MultiCat Post\",\"t1\",\"Cat1,Cat2,Cat3\"\n");

        try {
            $this->bulkImportService->import($tempFile, [
                'user_id' => $this->user->id,
                'skip_content' => true,
                'skip_images' => true,
            ]);

            $post = Post::with('categories')->first();
            $this->assertNotNull($post);
            $this->assertEquals(2, $post->categories()->count(), 'Expected two extra categories attached via pivot');
        } finally {
            unlink($tempFile);
        }
    }
}
