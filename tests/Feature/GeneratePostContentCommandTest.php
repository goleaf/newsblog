<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Services\MistralContentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Mockery;
use Tests\TestCase;

class GeneratePostContentCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ========== Basic Test Cases ==========

    public function test_command_finds_posts_without_content(): void
    {
        Config::set('mistral.api_key', 'test-api-key');

        $user = User::factory()->create();
        $category = Category::factory()->create();

        $postWithoutContent = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post Without Content',
            'content' => '',
        ]);

        $mockService = Mockery::mock(MistralContentService::class);
        $mockService->shouldReceive('generateContent')
            ->once()
            ->with('Post Without Content', $category->name)
            ->andReturn('# Generated Content\n\nThis is generated markdown content.');

        $this->app->instance(MistralContentService::class, $mockService);

        $this->artisan('posts:generate-content --force')
            ->expectsOutput('Searching for posts without content...')
            ->expectsOutput('Found 1 post(s) to process.')
            ->expectsOutputToContain('Processing: "Post Without Content"')
            ->expectsOutput('  ✓ Content generated successfully')
            ->assertExitCode(0);

        $postWithoutContent->refresh();
        $this->assertNotNull($postWithoutContent->content);
        $this->assertEquals('# Generated Content\n\nThis is generated markdown content.', $postWithoutContent->content);
    }

    public function test_command_skips_posts_with_existing_content(): void
    {
        Config::set('mistral.api_key', 'test-api-key');

        $user = User::factory()->create();
        $category = Category::factory()->create();

        Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post With Content',
            'content' => 'Existing content here',
        ]);

        $mockService = Mockery::mock(MistralContentService::class);
        $mockService->shouldNotReceive('generateContent');

        $this->app->instance(MistralContentService::class, $mockService);

        $this->artisan('posts:generate-content --force')
            ->expectsOutput('Searching for posts without content...')
            ->expectsOutput('No posts found without content.')
            ->assertExitCode(0);
    }

    public function test_command_handles_empty_results(): void
    {
        Config::set('mistral.api_key', 'test-api-key');

        $mockService = Mockery::mock(MistralContentService::class);
        $mockService->shouldNotReceive('generateContent');

        $this->app->instance(MistralContentService::class, $mockService);

        $this->artisan('posts:generate-content --force')
            ->expectsOutput('Searching for posts without content...')
            ->expectsOutput('No posts found without content.')
            ->assertExitCode(0);
    }

    public function test_command_handles_posts_with_empty_string_content(): void
    {
        Config::set('mistral.api_key', 'test-api-key');

        $user = User::factory()->create();
        $category = Category::factory()->create();

        $postWithEmptyContent = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post With Empty Content',
            'content' => '',
        ]);

        $mockService = Mockery::mock(MistralContentService::class);
        $mockService->shouldReceive('generateContent')
            ->once()
            ->with('Post With Empty Content', $category->name)
            ->andReturn('# Generated Content\n\nThis is generated markdown content.');

        $this->app->instance(MistralContentService::class, $mockService);

        $this->artisan('posts:generate-content --force')
            ->expectsOutput('Found 1 post(s) to process.')
            ->assertExitCode(0);
    }

    public function test_command_handles_posts_with_whitespace_only_content(): void
    {
        Config::set('mistral.api_key', 'test-api-key');

        $user = User::factory()->create();
        $category = Category::factory()->create();

        $postWithWhitespace = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post With Whitespace',
            'content' => '   ',
        ]);

        $mockService = Mockery::mock(MistralContentService::class);
        $mockService->shouldReceive('generateContent')
            ->once()
            ->with('Post With Whitespace', $category->name)
            ->andReturn('# Generated Content\n\nThis is generated markdown content.');

        $this->app->instance(MistralContentService::class, $mockService);

        $this->artisan('posts:generate-content --force')
            ->expectsOutput('Found 1 post(s) to process.')
            ->assertExitCode(0);
    }

    // ========== Command Options Tests ==========

    public function test_limit_option_limits_processing(): void
    {
        Config::set('mistral.api_key', 'test-api-key');

        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post1 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post 1',
            'content' => '',
        ]);

        $post2 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post 2',
            'content' => '',
        ]);

        Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post 3',
            'content' => '',
        ]);

        $mockService = Mockery::mock(MistralContentService::class);
        $mockService->shouldReceive('generateContent')
            ->times(2)
            ->andReturn('# Generated Content\n\nThis is generated markdown content.');

        $this->app->instance(MistralContentService::class, $mockService);

        $this->artisan('posts:generate-content --limit=2 --force')
            ->expectsOutput('Found 2 post(s) to process.')
            ->assertExitCode(0);
    }

    public function test_dry_run_option_shows_posts_without_generating_content(): void
    {
        Config::set('mistral.api_key', 'test-api-key');

        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post1 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post 1',
            'content' => '',
        ]);

        $post2 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post 2',
            'content' => '',
        ]);

        $mockService = Mockery::mock(MistralContentService::class);
        $mockService->shouldNotReceive('generateContent');

        $this->app->instance(MistralContentService::class, $mockService);

        $this->artisan('posts:generate-content --dry-run')
            ->expectsOutput('Found 2 post(s) to process.')
            ->expectsOutput('Dry run mode - posts that would be processed:')
            ->assertExitCode(0);

        $post1->refresh();
        $post2->refresh();
        $this->assertEmpty($post1->content);
        $this->assertEmpty($post2->content);
    }

    public function test_force_option_skips_confirmation(): void
    {
        Config::set('mistral.api_key', 'test-api-key');

        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post Without Content',
            'content' => '',
        ]);

        $mockService = Mockery::mock(MistralContentService::class);
        $mockService->shouldReceive('generateContent')
            ->once()
            ->andReturn('# Generated Content\n\nThis is generated markdown content.');

        $this->app->instance(MistralContentService::class, $mockService);

        $this->artisan('posts:generate-content --force')
            ->assertExitCode(0);
    }

    public function test_command_prompts_for_confirmation_when_force_not_used(): void
    {
        Config::set('mistral.api_key', 'test-api-key');

        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post Without Content',
            'content' => '',
        ]);

        $mockService = Mockery::mock(MistralContentService::class);
        $mockService->shouldReceive('generateContent')
            ->once()
            ->andReturn('# Generated Content\n\nThis is generated markdown content.');

        $this->app->instance(MistralContentService::class, $mockService);

        $this->artisan('posts:generate-content')
            ->expectsQuestion('Do you want to proceed?', true)
            ->assertExitCode(0);
    }

    public function test_command_cancels_when_confirmation_denied(): void
    {
        Config::set('mistral.api_key', 'test-api-key');

        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post Without Content',
            'content' => '',
        ]);

        $mockService = Mockery::mock(MistralContentService::class);
        $mockService->shouldNotReceive('generateContent');

        $this->app->instance(MistralContentService::class, $mockService);

        $this->artisan('posts:generate-content')
            ->expectsQuestion('Do you want to proceed?', false)
            ->expectsOutput('Operation cancelled.')
            ->assertExitCode(0);
    }

    // ========== Content Generation Flow Tests ==========

    public function test_successful_content_generation_updates_database(): void
    {
        Config::set('mistral.api_key', 'test-api-key');

        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Test Post',
            'content' => '',
        ]);

        $generatedContent = '# Test Article\n\n## Introduction\n\nThis is a comprehensive article.\n\n## Conclusion\n\nFinal thoughts.';

        $mockService = Mockery::mock(MistralContentService::class);
        $mockService->shouldReceive('generateContent')
            ->once()
            ->with('Test Post', $category->name)
            ->andReturn($generatedContent);

        $this->app->instance(MistralContentService::class, $mockService);

        $this->artisan('posts:generate-content --force')
            ->assertExitCode(0);

        $post->refresh();
        $this->assertEquals($generatedContent, $post->content);
    }

    public function test_command_displays_summary_statistics(): void
    {
        Config::set('mistral.api_key', 'test-api-key');

        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post1 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post 1',
            'content' => '',
        ]);

        $post2 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post 2',
            'content' => '',
        ]);

        $mockService = Mockery::mock(MistralContentService::class);
        $mockService->shouldReceive('generateContent')
            ->times(2)
            ->andReturn('# Generated Content\n\nThis is generated markdown content.');

        $this->app->instance(MistralContentService::class, $mockService);

        $this->artisan('posts:generate-content --force')
            ->expectsOutput('Summary:')
            ->expectsOutputToContain('Total posts processed:')
            ->expectsOutputToContain('Successful:')
            ->expectsOutputToContain('Failed:')
            ->expectsOutputToContain('Duration:')
            ->assertExitCode(0);
    }

    public function test_command_processes_posts_sequentially(): void
    {
        Config::set('mistral.api_key', 'test-api-key');

        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post1 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post 1',
            'content' => '',
        ]);

        $post2 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post 2',
            'content' => '',
        ]);

        $mockService = Mockery::mock(MistralContentService::class);
        $mockService->shouldReceive('generateContent')
            ->once()
            ->with('Post 1', $category->name)
            ->andReturn('# Generated Content 1');
        $mockService->shouldReceive('generateContent')
            ->once()
            ->with('Post 2', $category->name)
            ->andReturn('# Generated Content 2');

        $this->app->instance(MistralContentService::class, $mockService);

        $this->artisan('posts:generate-content --force')
            ->expectsOutput('Processing: "Post 1" [1/2]')
            ->expectsOutput('Processing: "Post 2" [2/2]')
            ->assertExitCode(0);

        $post1->refresh();
        $post2->refresh();
        $this->assertEquals('# Generated Content 1', $post1->content);
        $this->assertEquals('# Generated Content 2', $post2->content);
    }

    public function test_command_handles_posts_with_category(): void
    {
        Config::set('mistral.api_key', 'test-api-key');

        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post With Category',
            'content' => '',
        ]);

        $mockService = Mockery::mock(MistralContentService::class);
        $mockService->shouldReceive('generateContent')
            ->once()
            ->with('Post With Category', $category->name)
            ->andReturn('# Generated Content\n\nThis is generated markdown content.');

        $this->app->instance(MistralContentService::class, $mockService);

        $this->artisan('posts:generate-content --force')
            ->assertExitCode(0);
    }

    // ========== Error Handling Tests ==========

    public function test_command_handles_missing_api_key_gracefully(): void
    {
        Config::set('mistral.api_key', '');

        $this->artisan('posts:generate-content --force')
            ->expectsOutput('Mistral API key is not configured. Please set MISTRAL_API_KEY in your .env file.')
            ->assertExitCode(1);
    }

    public function test_command_continues_processing_on_api_failure(): void
    {
        Config::set('mistral.api_key', 'test-api-key');

        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post1 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post 1',
            'content' => '',
        ]);

        $post2 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post 2',
            'content' => '',
        ]);

        $mockService = Mockery::mock(MistralContentService::class);
        $mockService->shouldReceive('generateContent')
            ->once()
            ->with('Post 1', $category->name)
            ->andThrow(new \RuntimeException('API request failed'));
        $mockService->shouldReceive('generateContent')
            ->once()
            ->with('Post 2', $category->name)
            ->andReturn('# Generated Content\n\nThis is generated markdown content.');

        $this->app->instance(MistralContentService::class, $mockService);

        $this->artisan('posts:generate-content --force')
            ->expectsOutput('  ✗ Failed to generate content')
            ->expectsOutput('  ✓ Content generated successfully')
            ->assertExitCode(1);

        $post1->refresh();
        $post2->refresh();
        $this->assertEmpty($post1->content);
        $this->assertNotEmpty($post2->content);
    }

    public function test_command_logs_errors_on_failure(): void
    {
        Config::set('mistral.api_key', 'test-api-key');

        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post With Error',
            'content' => '',
        ]);

        $mockService = Mockery::mock(MistralContentService::class);
        $mockService->shouldReceive('generateContent')
            ->once()
            ->with('Post With Error', $category->name)
            ->andThrow(new \RuntimeException('API request failed'));

        $this->app->instance(MistralContentService::class, $mockService);

        $this->artisan('posts:generate-content --force')
            ->expectsOutput('  ✗ Failed to generate content')
            ->assertExitCode(1);

        $post->refresh();
        $this->assertEmpty($post->content);
    }

    public function test_command_displays_correct_summary_with_failures(): void
    {
        Config::set('mistral.api_key', 'test-api-key');

        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post1 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post 1',
            'content' => '',
        ]);

        $post2 = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post 2',
            'content' => '',
        ]);

        $mockService = Mockery::mock(MistralContentService::class);
        $mockService->shouldReceive('generateContent')
            ->once()
            ->andThrow(new \RuntimeException('API error'));
        $mockService->shouldReceive('generateContent')
            ->once()
            ->andReturn('# Generated Content');

        $this->app->instance(MistralContentService::class, $mockService);

        $this->artisan('posts:generate-content --force')
            ->expectsOutputToContain('Total posts processed: 2')
            ->expectsOutputToContain('Successful: 1')
            ->expectsOutputToContain('Failed: 1')
            ->assertExitCode(1);
    }
}
