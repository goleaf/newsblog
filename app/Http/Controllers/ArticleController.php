<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use App\Services\ArticleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ArticleController extends Controller
{
    public function __construct(
        protected ArticleService $articleService
    ) {}

    /**
     * Display a listing of articles with pagination.
     * Requirements: 1.1
     */
    public function index(Request $request)
    {
        $query = Article::published()
            ->with(['author:id,name,avatar', 'category:id,name,slug', 'tags:id,name,slug'])
            ->select(['id', 'title', 'slug', 'excerpt', 'featured_image', 'published_at', 'reading_time', 'view_count', 'user_id', 'category_id']);

        // Apply filters
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('author')) {
            $query->where('user_id', $request->author);
        }

        if ($request->filled('tag')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('tags.id', $request->tag);
            });
        }

        // Apply sorting
        $sort = $request->get('sort', 'latest');
        match ($sort) {
            'popular' => $query->orderBy('view_count', 'desc'),
            'oldest' => $query->orderBy('published_at', 'asc'),
            default => $query->orderBy('published_at', 'desc'),
        };

        $articles = $query->paginate(15)->withQueryString();

        return view('articles.index', compact('articles'));
    }

    /**
     * Display the specified article with view tracking.
     * Requirements: 1.2, 4.3, 8.1
     */
    public function show(string $slug, Request $request)
    {
        $article = Article::where('slug', $slug)
            ->published()
            ->with([
                'author:id,name,bio,avatar',
                'category:id,name,slug',
                'tags:id,name,slug',
                'comments' => function ($query) {
                    $query->where('status', 'approved')
                        ->whereNull('parent_id')
                        ->with(['replies' => function ($q) {
                            $q->where('status', 'approved')
                                ->with(['author:id,name,avatar'])
                                ->orderBy('created_at', 'asc');
                        }, 'author:id,name,avatar'])
                        ->orderBy('created_at', 'desc');
                },
            ])
            ->firstOrFail();

        // Track view (handled by middleware or service)
        $this->articleService->trackView($article, $request);

        return view('articles.show', compact('article'));
    }

    /**
     * Show the form for creating a new article.
     * Requirements: 1.3
     */
    public function create()
    {
        Gate::authorize('create', Article::class);

        $categories = Category::orderBy('name')->get(['id', 'name']);
        $tags = Tag::orderBy('name')->get(['id', 'name']);

        return view('articles.create', compact('categories', 'tags'));
    }

    /**
     * Store a newly created article in storage.
     * Requirements: 1.3, 1.4
     */
    public function store(StoreArticleRequest $request)
    {
        $article = $this->articleService->create($request->validated(), $request->user());

        return redirect()
            ->route('articles.show', $article->slug)
            ->with('success', 'Article created successfully.');
    }

    /**
     * Show the form for editing the specified article.
     * Requirements: 1.3
     */
    public function edit(Article $article)
    {
        Gate::authorize('update', $article);

        $categories = Category::orderBy('name')->get(['id', 'name']);
        $tags = Tag::orderBy('name')->get(['id', 'name']);
        $selectedTags = $article->tags->pluck('id')->toArray();

        return view('articles.edit', compact('article', 'categories', 'tags', 'selectedTags'));
    }

    /**
     * Update the specified article in storage.
     * Requirements: 1.3, 1.4
     */
    public function update(UpdateArticleRequest $request, Article $article)
    {
        $article = $this->articleService->update($article, $request->validated());

        return redirect()
            ->route('articles.show', $article->slug)
            ->with('success', 'Article updated successfully.');
    }

    /**
     * Remove the specified article from storage (soft delete).
     * Requirements: 1.4
     */
    public function destroy(Article $article)
    {
        Gate::authorize('delete', $article);

        $article->delete();

        return redirect()
            ->route('articles.index')
            ->with('success', 'Article deleted successfully.');
    }

    /**
     * Publish the specified article.
     * Requirements: 1.4
     */
    public function publish(Article $article)
    {
        Gate::authorize('update', $article);

        $this->articleService->publish($article);

        return redirect()
            ->route('articles.show', $article->slug)
            ->with('success', 'Article published successfully.');
    }

    /**
     * Unpublish the specified article.
     * Requirements: 1.4
     */
    public function unpublish(Article $article)
    {
        Gate::authorize('update', $article);

        $this->articleService->unpublish($article);

        return redirect()
            ->route('articles.edit', $article)
            ->with('success', 'Article unpublished successfully.');
    }
}
