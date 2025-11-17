<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ArticleSimilarity;
use App\Models\Post;
use App\Models\Recommendation;
use App\Models\User;
use App\Models\UserReadingHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecommendationService
{
    /**
     * Generate personalized recommendations for a user.
     */
    public function generateRecommendations(User $user, int $limit = 10): Collection
    {
        // Get user's reading history
        $readArticles = $user->postViews()
            ->pluck('post_id')
            ->unique();

        // Content-based: similar to recently read articles
        $contentBased = $this->getContentBasedRecommendations($readArticles, $limit);

        // Collaborative: what similar users read
        $collaborative = $this->getCollaborativeRecommendations($user, $limit);

        // Trending: popular recent articles
        $trending = $this->getTrendingArticles($limit);

        // Combine and rank
        return $this->combineAndRank([
            'content' => $contentBased,
            'collaborative' => $collaborative,
            'trending' => $trending,
        ], $readArticles, $limit);
    }

    /**
     * Get content-based recommendations using article similarities.
     */
    public function getContentBasedRecommendations(Collection $readArticles, int $limit = 10): Collection
    {
        if ($readArticles->isEmpty()) {
            return collect();
        }

        // Get similar articles based on recently read articles
        return ArticleSimilarity::whereIn('post_id', $readArticles->take(5))
            ->with(['similarPost' => function ($query) {
                $query->where('status', 'published')
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
            }])
            ->orderByDesc('score')
            ->limit($limit * 2)
            ->get()
            ->map(function ($similarity) {
                return [
                    'post_id' => $similarity->similar_post_id,
                    'score' => $similarity->score,
                    'reason' => 'similar_content',
                    'post' => $similarity->similarPost,
                ];
            })
            ->filter(fn ($item) => $item['post'] !== null);
    }

    /**
     * Get collaborative filtering recommendations.
     */
    public function getCollaborativeRecommendations(User $user, int $limit = 10): Collection
    {
        // Find users with similar reading patterns
        $similarUsers = $this->findSimilarUsers($user, 10);

        if ($similarUsers->isEmpty()) {
            return collect();
        }

        // Get articles read by similar users
        return UserReadingHistory::whereIn('user_id', $similarUsers->pluck('user_id'))
            ->select('post_id', DB::raw('COUNT(*) as read_count'))
            ->groupBy('post_id')
            ->orderByDesc('read_count')
            ->limit($limit * 2)
            ->get()
            ->map(function ($item) {
                $post = Post::where('id', $item->post_id)
                    ->where('status', 'published')
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now())
                    ->first();

                return [
                    'post_id' => $item->post_id,
                    'score' => $item->read_count / 10, // Normalize score
                    'reason' => 'collaborative',
                    'post' => $post,
                ];
            })
            ->filter(fn ($item) => $item['post'] !== null);
    }

    /**
     * Get trending articles.
     */
    public function getTrendingArticles(int $limit = 10): Collection
    {
        $sevenDaysAgo = now()->subDays(7);

        return Post::where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where('published_at', '>=', $sevenDaysAgo)
            ->withCount(['postViews as views_count' => function ($query) use ($sevenDaysAgo) {
                $query->where('created_at', '>=', $sevenDaysAgo);
            }])
            ->orderByDesc('views_count')
            ->limit($limit)
            ->get()
            ->map(function ($post) {
                return [
                    'post_id' => $post->id,
                    'score' => $post->views_count / 100, // Normalize score
                    'reason' => 'trending',
                    'post' => $post,
                ];
            });
    }

    /**
     * Find users with similar reading patterns.
     */
    protected function findSimilarUsers(User $user, int $limit = 10): Collection
    {
        // Get user's reading history
        $userReads = $user->postViews()->pluck('post_id');

        if ($userReads->isEmpty()) {
            return collect();
        }

        // Find users who read similar articles
        return UserReadingHistory::whereIn('post_id', $userReads)
            ->where('user_id', '!=', $user->id)
            ->select('user_id', DB::raw('COUNT(*) as common_reads'))
            ->groupBy('user_id')
            ->orderByDesc('common_reads')
            ->limit($limit)
            ->get();
    }

    /**
     * Combine and rank recommendations from different strategies.
     */
    protected function combineAndRank(array $recommendations, Collection $readArticles, int $limit): Collection
    {
        $combined = collect();

        // Merge all recommendations
        foreach ($recommendations as $source => $items) {
            $combined = $combined->merge($items);
        }

        // Remove already read articles
        $combined = $combined->filter(function ($item) use ($readArticles) {
            return ! $readArticles->contains($item['post_id']);
        });

        // Group by post_id and sum scores
        $grouped = $combined->groupBy('post_id')->map(function ($group) {
            $totalScore = $group->sum('score');
            $reasons = $group->pluck('reason')->unique()->values();

            return [
                'post_id' => $group->first()['post_id'],
                'score' => $totalScore,
                'reason' => $reasons->first(), // Primary reason
                'post' => $group->first()['post'],
            ];
        });

        // Sort by score and limit
        return $grouped->sortByDesc('score')
            ->take($limit)
            ->values();
    }

    /**
     * Calculate article similarity scores using TF-IDF.
     */
    public function calculateArticleSimilarity(Post $article): Collection
    {
        // Get all published articles except the current one
        $articles = Post::where('status', 'published')
            ->where('id', '!=', $article->id)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with(['tags', 'categories'])
            ->get();

        $similarities = collect();

        foreach ($articles as $otherArticle) {
            $score = $this->calculateSimilarityScore($article, $otherArticle);

            if ($score > 0.1) { // Only store meaningful similarities
                $similarities->push([
                    'post_id' => $article->id,
                    'similar_post_id' => $otherArticle->id,
                    'score' => $score,
                ]);
            }
        }

        return $similarities->sortByDesc('score')->take(20);
    }

    /**
     * Calculate similarity score between two articles.
     */
    protected function calculateSimilarityScore(Post $article1, Post $article2): float
    {
        $score = 0.0;

        // Category similarity (40% weight)
        $categories1 = $article1->categories->pluck('id');
        $categories2 = $article2->categories->pluck('id');
        $categoryOverlap = $categories1->intersect($categories2)->count();
        $categoryUnion = $categories1->merge($categories2)->unique()->count();

        if ($categoryUnion > 0) {
            $score += ($categoryOverlap / $categoryUnion) * 0.4;
        }

        // Tag similarity (40% weight)
        $tags1 = $article1->tags->pluck('id');
        $tags2 = $article2->tags->pluck('id');
        $tagOverlap = $tags1->intersect($tags2)->count();
        $tagUnion = $tags1->merge($tags2)->unique()->count();

        if ($tagUnion > 0) {
            $score += ($tagOverlap / $tagUnion) * 0.4;
        }

        // Title similarity (20% weight)
        $titleSimilarity = $this->calculateTextSimilarity($article1->title, $article2->title);
        $score += $titleSimilarity * 0.2;

        return round($score, 4);
    }

    /**
     * Calculate text similarity using word overlap.
     */
    protected function calculateTextSimilarity(string $text1, string $text2): float
    {
        $words1 = collect(str_word_count(strtolower($text1), 1));
        $words2 = collect(str_word_count(strtolower($text2), 1));

        if ($words1->isEmpty() || $words2->isEmpty()) {
            return 0.0;
        }

        $overlap = $words1->intersect($words2)->count();
        $union = $words1->merge($words2)->unique()->count();

        return $union > 0 ? $overlap / $union : 0.0;
    }

    /**
     * Store recommendations for a user.
     */
    public function storeRecommendations(User $user, Collection $recommendations): void
    {
        // Delete old recommendations
        Recommendation::where('user_id', $user->id)->delete();

        // Store new recommendations
        $data = $recommendations->map(function ($item, $index) use ($user) {
            return [
                'user_id' => $user->id,
                'post_id' => $item['post_id'],
                'score' => $item['score'],
                'reason' => $item['reason'],
                'generated_at' => now(),
                'clicked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        });

        Recommendation::insert($data->toArray());
    }

    /**
     * Get similar articles for a given article.
     */
    public function getSimilarArticles(Post $article, int $limit = 5): Collection
    {
        return ArticleSimilarity::where('post_id', $article->id)
            ->with(['similarPost' => function ($query) {
                $query->where('status', 'published')
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now())
                    ->with(['user', 'categories', 'tags']);
            }])
            ->orderByDesc('score')
            ->limit($limit)
            ->get()
            ->pluck('similarPost')
            ->filter();
    }

    /**
     * Track recommendation click.
     */
    public function trackClick(User $user, int $postId): void
    {
        Recommendation::where('user_id', $user->id)
            ->where('post_id', $postId)
            ->update([
                'clicked' => true,
                'clicked_at' => now(),
            ]);
    }

    /**
     * Track recommendation impression.
     */
    public function trackImpression(User $user, int $postId): void
    {
        Recommendation::where('user_id', $user->id)
            ->where('post_id', $postId)
            ->increment('impressions');
    }

    /**
     * Calculate click-through rate for recommendations.
     */
    public function calculateClickThroughRate(?User $user = null, ?string $reason = null): float
    {
        $query = Recommendation::query();

        if ($user) {
            $query->where('user_id', $user->id);
        }

        if ($reason) {
            $query->where('reason', $reason);
        }

        $total = $query->where('impressions', '>', 0)->count();

        if ($total === 0) {
            return 0.0;
        }

        $clicked = $query->where('clicked', true)->count();

        return round(($clicked / $total) * 100, 2);
    }

    /**
     * Get recommendation performance metrics.
     */
    public function getPerformanceMetrics(): array
    {
        $totalRecommendations = Recommendation::count();
        $totalClicked = Recommendation::where('clicked', true)->count();
        $totalImpressions = Recommendation::sum('impressions');

        $byReason = Recommendation::select('reason')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN clicked = 1 THEN 1 ELSE 0 END) as clicked')
            ->selectRaw('SUM(impressions) as impressions')
            ->groupBy('reason')
            ->get()
            ->map(function ($item) {
                $ctr = $item->impressions > 0 ? round(($item->clicked / $item->impressions) * 100, 2) : 0;

                return [
                    'reason' => $item->reason,
                    'total' => $item->total,
                    'clicked' => $item->clicked,
                    'impressions' => $item->impressions,
                    'ctr' => $ctr,
                ];
            });

        return [
            'total_recommendations' => $totalRecommendations,
            'total_clicked' => $totalClicked,
            'total_impressions' => $totalImpressions,
            'overall_ctr' => $totalImpressions > 0 ? round(($totalClicked / $totalImpressions) * 100, 2) : 0,
            'by_reason' => $byReason,
        ];
    }
}
