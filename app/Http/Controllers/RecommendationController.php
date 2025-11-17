<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Recommendation;
use App\Services\RecommendationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RecommendationController extends Controller
{
    public function __construct(
        protected RecommendationService $recommendationService
    ) {}

    /**
     * Display personalized recommendations for the authenticated user.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        if (! $user) {
            // For guests, show trending articles
            $recommendations = $this->recommendationService->getTrendingArticles(20);
        } else {
            // Get stored recommendations or generate new ones
            $recommendations = Recommendation::where('user_id', $user->id)
                ->with(['post.user', 'post.categories', 'post.tags'])
                ->whereHas('post', function ($query) {
                    $query->where('status', 'published')
                        ->whereNotNull('published_at')
                        ->where('published_at', '<=', now());
                })
                ->orderByDesc('score')
                ->limit(20)
                ->get();

            // If no recommendations exist, generate them
            if ($recommendations->isEmpty()) {
                $generatedRecommendations = $this->recommendationService->generateRecommendations($user, 20);
                $this->recommendationService->storeRecommendations($user, $generatedRecommendations);

                // Reload recommendations
                $recommendations = Recommendation::where('user_id', $user->id)
                    ->with(['post.user', 'post.categories', 'post.tags'])
                    ->orderByDesc('score')
                    ->get();
            }
        }

        return view('recommendations.index', [
            'recommendations' => $recommendations,
        ]);
    }

    /**
     * Get similar articles for a specific article.
     */
    public function similar(Post $post): View
    {
        $similarArticles = $this->recommendationService->getSimilarArticles($post, 5);

        return view('recommendations.similar', [
            'article' => $post,
            'similarArticles' => $similarArticles,
        ]);
    }

    /**
     * Track when a user clicks on a recommendation.
     */
    public function trackClick(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'post_id' => 'required|integer|exists:posts,id',
        ]);

        $user = Auth::user();

        if ($user) {
            $this->recommendationService->trackClick($user, $request->post_id);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Track when a recommendation is shown to a user.
     */
    public function trackImpression(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'post_id' => 'required|integer|exists:posts,id',
        ]);

        $user = Auth::user();

        if ($user) {
            $this->recommendationService->trackImpression($user, $request->post_id);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Get recommendation performance metrics (admin only).
     */
    public function metrics(): View|\Illuminate\Http\JsonResponse
    {
        $user = Auth::user();

        if (! $user || ! in_array($user->role->value, ['admin', 'moderator'])) {
            abort(403, 'Unauthorized');
        }

        $metrics = $this->recommendationService->getPerformanceMetrics();

        if (request()->wantsJson()) {
            return response()->json($metrics);
        }

        return view('admin.recommendations.metrics', [
            'metrics' => $metrics,
        ]);
    }
}
