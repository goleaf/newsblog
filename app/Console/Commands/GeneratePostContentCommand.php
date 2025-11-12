<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\MistralContentService;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class GeneratePostContentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posts:generate-content
                            {--limit= : Limit the number of posts to process}
                            {--dry-run : Show posts that would be processed without generating content}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate content for posts that have titles but no content using Mistral AI';

    protected MistralContentService $contentService;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $this->contentService = app(MistralContentService::class);
        } catch (\InvalidArgumentException $e) {
            $this->error('Mistral API key is not configured. Please set MISTRAL_API_KEY in your .env file.');

            return self::FAILURE;
        }

        $this->info('Searching for posts without content...');

        $posts = $this->getPostsWithoutContent();

        if ($posts->isEmpty()) {
            $this->info('No posts found without content.');

            return self::SUCCESS;
        }

        $this->info("Found {$posts->count()} post(s) to process.");

        if ($this->option('dry-run')) {
            return $this->handleDryRun($posts);
        }

        if (! $this->option('force')) {
            if (! $this->confirm('Do you want to proceed?', true)) {
                $this->info('Operation cancelled.');

                return self::SUCCESS;
            }
        }

        $startTime = microtime(true);
        $total = $posts->count();
        $success = 0;
        $failed = 0;

        $this->newLine();

        foreach ($posts as $index => $post) {
            $current = $index + 1;
            $this->line("Processing: \"{$post->title}\" [{$current}/{$total}]");

            if ($this->processPost($post)) {
                $this->info('  ✓ Content generated successfully');
                $success++;
            } else {
                $this->error('  ✗ Failed to generate content');
                $failed++;
            }

            $this->newLine();
        }

        $duration = round(microtime(true) - $startTime, 2);
        $this->displaySummary($total, $success, $failed, $duration);

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Get posts without content.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getPostsWithoutContent()
    {
        $query = Post::withoutContent()
            ->with('category:id,name')
            ->select('id', 'title', 'content', 'category_id');

        if ($this->option('limit')) {
            $query->limit((int) $this->option('limit'));
        }

        return $query->get();
    }

    /**
     * Process a single post by generating content and updating the database.
     */
    protected function processPost(Post $post): bool
    {
        try {
            $categoryName = $post->category?->name;

            $content = $this->contentService->generateContent($post->title, $categoryName);

            try {
                $post->update(['content' => $content]);

                return true;
            } catch (QueryException $e) {
                Log::channel('mistral')->error('Database update failed', [
                    'post_id' => $post->id,
                    'title' => $post->title,
                    'error' => $e->getMessage(),
                ]);

                return false;
            }
        } catch (\RuntimeException $e) {
            Log::channel('mistral')->error('Content generation failed', [
                'post_id' => $post->id,
                'title' => $post->title,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Display summary statistics.
     */
    protected function displaySummary(int $total, int $success, int $failed, float $duration): void
    {
        $this->newLine();
        $this->info('Summary:');
        $this->info('--------');
        $this->line("Total posts processed: {$total}");
        $this->line("Successful: {$success}");
        $this->line("Failed: {$failed}");
        $this->line("Duration: {$duration} seconds");
    }

    /**
     * Handle dry-run mode.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $posts
     */
    protected function handleDryRun($posts): int
    {
        $this->newLine();
        $this->info('Dry run mode - posts that would be processed:');
        $this->newLine();

        $rows = $posts->map(function ($post) {
            return [
                $post->id,
                $post->title,
                $post->category?->name ?? 'N/A',
            ];
        })->toArray();

        $this->table(['ID', 'Title', 'Category'], $rows);

        return self::SUCCESS;
    }
}
