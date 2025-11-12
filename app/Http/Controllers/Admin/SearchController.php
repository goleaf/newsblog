<?php

namespace App\Http\Controllers\Admin;

use App\DataTransferObjects\SearchResult;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SearchRequest;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Services\FuzzySearchService;
use App\Services\SearchAnalyticsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    public function __construct(
        protected FuzzySearchService $fuzzySearchService,
        protected SearchAnalyticsService $analyticsService
    ) {}

    /**
     * Admin search across multiple types (posts, users, comments)
     */
    public function index(SearchRequest $request)
    {
        $query = $request->validated()['q'];
        $type = $request->validated()['type'] ?? 'all';
        $limit = $request->validated()['limit'] ?? 20;

        $results = collect();

        try {
            if ($type === 'all' || $type === 'posts') {
                $posts = $this->searchPosts($query, $limit);
                $results = $results->merge($posts);
            }

            if ($type === 'all' || $type === 'users') {
                $users = $this->searchUsers($query, $limit);
                $results = $results->merge($users);
            }

            if ($type === 'all' || $type === 'comments') {
                $comments = $this->searchComments($query, $limit);
                $results = $results->merge($comments);
            }

            // Sort by relevance score
            $results = $results->sortByDesc('relevanceScore')->values();

            // Limit total results
            $results = $results->take($limit);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $results->map(fn ($result) => $result->toArray()),
                    'meta' => [
                        'query' => $query,
                        'type' => $type,
                        'count' => $results->count(),
                    ],
                ]);
            }

            return view('admin.search.index', [
                'results' => $results,
                'query' => $query,
                'type' => $type,
            ]);
        } catch (\Exception $e) {
            Log::error('Admin search error', [
                'query' => $query,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Search failed. Please try again.',
                    'data' => [],
                ], 500);
            }

            return back()->with('error', 'Search failed. Please try again.');
        }
    }

    /**
     * Search posts
     */
    protected function searchPosts(string $query, int $limit): Collection
    {
        if ($this->fuzzySearchService->isEnabled('posts')) {
            try {
                return $this->fuzzySearchService->searchPosts($query, [
                    'limit' => $limit,
                ]);
            } catch (\Exception $e) {
                Log::warning('Fuzzy post search failed, using basic search', [
                    'query' => $query,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Fallback to basic search
        $posts = Post::query()
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('content', 'like', "%{$query}%")
                    ->orWhere('excerpt', 'like', "%{$query}%");
            })
            ->with(['user', 'category', 'tags'])
            ->latest()
            ->limit($limit)
            ->get();

        return $posts->map(function ($post) {
            return SearchResult::fromPost($post, 50.0, []);
        });
    }

    /**
     * Search users
     */
    protected function searchUsers(string $query, int $limit): Collection
    {
        $users = User::query()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('bio', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->get();

        return $users->map(function ($user) use ($query) {
            // Calculate simple relevance score
            $score = 50.0;
            if (stripos($user->name, $query) !== false) {
                $score = 80.0;
            } elseif (stripos($user->email, $query) !== false) {
                $score = 70.0;
            }

            return new SearchResult(
                id: $user->id,
                type: 'user',
                title: $user->name,
                excerpt: $user->bio,
                url: route('admin.users.index', ['search' => $query]),
                relevanceScore: $score,
                highlights: [],
                metadata: [
                    'email' => $user->email,
                    'role' => $user->role,
                ]
            );
        });
    }

    /**
     * Search comments
     */
    protected function searchComments(string $query, int $limit): Collection
    {
        $comments = Comment::query()
            ->where(function ($q) use ($query) {
                $q->where('content', 'like', "%{$query}%")
                    ->orWhere('author_name', 'like', "%{$query}%")
                    ->orWhere('author_email', 'like', "%{$query}%");
            })
            ->with(['post', 'user'])
            ->latest()
            ->limit($limit)
            ->get();

        return $comments->map(function ($comment) use ($query) {
            // Calculate simple relevance score
            $score = 50.0;
            if (stripos($comment->content, $query) !== false) {
                $score = 75.0;
            }

            $excerpt = mb_substr(strip_tags($comment->content), 0, 200);

            return new SearchResult(
                id: $comment->id,
                type: 'comment',
                title: $comment->author_name ?? ($comment->user?->name ?? 'Anonymous'),
                excerpt: $excerpt,
                url: $comment->post ? route('post.show', $comment->post->slug).'#comment-'.$comment->id : '#',
                relevanceScore: $score,
                highlights: [],
                metadata: [
                    'post_id' => $comment->post_id,
                    'post_title' => $comment->post?->title,
                    'status' => $comment->status,
                    'created_at' => $comment->created_at?->toISOString(),
                ]
            );
        });
    }

    /**
     * Display search analytics dashboard
     */
    public function analytics()
    {
        $period = request()->get('period', 'month');

        $topQueries = $this->analyticsService->getTopQueries(20, $period);
        $noResultQueries = $this->analyticsService->getNoResultQueries(50);
        $performanceMetrics = $this->analyticsService->getPerformanceMetrics($period);

        // Get recent searches
        $recentSearches = \App\Models\SearchLog::with('user')
            ->latest()
            ->take(20)
            ->get();

        return view('admin.search.analytics', compact(
            'topQueries',
            'noResultQueries',
            'performanceMetrics',
            'recentSearches',
            'period'
        ));
    }
}
