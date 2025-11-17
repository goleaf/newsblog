<?php

namespace App\Jobs;

use App\Models\ArticleSimilarity;
use App\Models\Post;
use App\Services\RecommendationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CalculateArticleSimilaritiesJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ?int $postId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(RecommendationService $recommendationService): void
    {
        if ($this->postId) {
            // Calculate similarities for a specific article
            $article = Post::find($this->postId);

            if (! $article) {
                Log::warning("Article {$this->postId} not found for similarity calculation");

                return;
            }

            $this->calculateForArticle($article, $recommendationService);
        } else {
            // Calculate similarities for all published articles
            $articles = Post::where('status', 'published')
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->get();

            foreach ($articles as $article) {
                $this->calculateForArticle($article, $recommendationService);
            }
        }
    }

    /**
     * Calculate similarities for a single article.
     */
    protected function calculateForArticle(Post $article, RecommendationService $recommendationService): void
    {
        // Delete existing similarities for this article
        ArticleSimilarity::where('post_id', $article->id)->delete();

        // Calculate new similarities
        $similarities = $recommendationService->calculateArticleSimilarity($article);

        // Store similarities
        if ($similarities->isNotEmpty()) {
            ArticleSimilarity::insert($similarities->toArray());
            Log::info("Calculated {$similarities->count()} similarities for article {$article->id}");
        }
    }
}
