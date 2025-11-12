<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RebuildSearchIndexCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_rebuilds_all_indexes_by_default(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        Post::factory()->count(3)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $this->artisan('search:rebuild-index')
            ->expectsOutput('Starting search index rebuild...')
            ->assertExitCode(0);
    }

    public function test_command_rebuilds_specific_index_type(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->count(2)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $this->artisan('search:rebuild-index --type=posts')
            ->expectsOutput('Starting search index rebuild...')
            ->assertExitCode(0);
    }

    public function test_command_rebuilds_all_indexes_with_all_option(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        Tag::factory()->count(2)->create();

        Post::factory()->count(2)->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $this->artisan('search:rebuild-index --all')
            ->expectsOutput('Starting search index rebuild...')
            ->assertExitCode(0);
    }

    public function test_command_fails_with_invalid_index_type(): void
    {
        $this->artisan('search:rebuild-index --type=invalid')
            ->expectsOutput('Invalid index type: invalid. Valid types are: posts, tags, categories')
            ->assertExitCode(1);
    }

    public function test_command_rebuilds_tags_index(): void
    {
        Tag::factory()->count(3)->create();

        $this->artisan('search:rebuild-index --type=tags')
            ->expectsOutput('Starting search index rebuild...')
            ->assertExitCode(0);
    }

    public function test_command_rebuilds_categories_index(): void
    {
        Category::factory()->count(3)->create();

        $this->artisan('search:rebuild-index --type=categories')
            ->expectsOutput('Starting search index rebuild...')
            ->assertExitCode(0);
    }

    public function test_command_clears_cache_before_rebuilding(): void
    {
        Cache::put('fuzzy_search:index:posts', ['old_data'], 3600);

        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $this->artisan('search:rebuild-index --type=posts')
            ->assertExitCode(0);

        $index = Cache::get('fuzzy_search:index:posts');
        $this->assertIsArray($index);
        $this->assertNotEmpty($index);
        $this->assertNotEquals(['old_data'], $index);
    }
}
