<?php

use App\Http\Controllers\Admin\SearchController as AdminSearchController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PostController as PublicPostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Sitemap routes
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.index');
Route::get('/post/{slug}', [PublicPostController::class, 'show'])->name('post.show');
Route::get('/category/{slug}', [PublicPostController::class, 'category'])->name('category.show');
Route::get('/tag/{slug}', [PublicPostController::class, 'tag'])->name('tag.show');
Route::get('/series', [\App\Http\Controllers\SeriesController::class, 'index'])->name('series.index');
Route::get('/series/{slug}', [\App\Http\Controllers\SeriesController::class, 'show'])->name('series.show');
Route::get('/search', [SearchController::class, 'index'])
    ->middleware('throttle:search')
    ->name('search');
Route::get('/search/suggestions', [SearchController::class, 'suggestions'])
    ->middleware('throttle:search')
    ->name('search.suggestions');
Route::post('/comments', [CommentController::class, 'store'])
    ->middleware('throttle:comments')
    ->name('comments.store');

// Newsletter routes
Route::post('/newsletter/subscribe', [\App\Http\Controllers\NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
Route::get('/newsletter/verify/{token}', [\App\Http\Controllers\NewsletterController::class, 'verify'])->name('newsletter.verify');
Route::get('/newsletter/unsubscribe/{token}', [\App\Http\Controllers\NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');

Route::get('/dashboard', function () {
    $searchStats = null;
    $metrics = null;

    // Load search stats and metrics for admin/editor users
    $user = auth()->user();
    if ($user && ($user->isAdmin() || $user->isEditor())) {
        $searchStats = [
            'recent_searches' => \App\Models\SearchLog::with('user')
                ->latest()
                ->limit(5)
                ->get(),
            'popular_queries' => \App\Models\SearchLog::query()
                ->select('query', \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'))
                ->where('created_at', '>=', now()->subDays(7))
                ->groupBy('query')
                ->orderByDesc('count')
                ->limit(5)
                ->get(),
            'total_today' => \App\Models\SearchLog::whereDate('created_at', today())->count(),
        ];

        // Get dashboard metrics
        $dashboardService = app(\App\Services\DashboardService::class);
        $metrics = $dashboardService->getMetrics();
    }

    return view('dashboard', compact('searchStats', 'metrics'));
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Bookmarks
    Route::get('/bookmarks', [\App\Http\Controllers\BookmarkController::class, 'index'])->name('bookmarks.index');
    Route::post('/posts/{post}/bookmark', [\App\Http\Controllers\BookmarkController::class, 'toggle'])->name('bookmarks.toggle');
});

// Admin routes
Route::middleware(['auth', 'role:admin,editor'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/search', [AdminSearchController::class, 'index'])->name('search');
    Route::get('/search/analytics', [AdminSearchController::class, 'analytics'])->name('search.analytics');

    // Performance monitoring
    Route::get('/performance', [\App\Http\Controllers\Admin\PerformanceController::class, 'index'])->name('performance');

    // Post revision routes
    Route::prefix('posts/{post}/revisions')->name('posts.revisions.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\PostRevisionController::class, 'index'])->name('index');
        Route::get('/compare', [\App\Http\Controllers\Admin\PostRevisionController::class, 'compare'])->name('compare');
        Route::get('/{revision}', [\App\Http\Controllers\Admin\PostRevisionController::class, 'show'])->name('show');
        Route::post('/{revision}/restore', [\App\Http\Controllers\Admin\PostRevisionController::class, 'restore'])->name('restore');
        Route::delete('/{revision}', [\App\Http\Controllers\Admin\PostRevisionController::class, 'destroy'])->name('destroy');
    });

    // Series routes
    Route::resource('series', \App\Http\Controllers\Admin\SeriesController::class);
    Route::post('/series/{series}/posts', [\App\Http\Controllers\Admin\SeriesController::class, 'addPost'])->name('series.posts.add');
    Route::delete('/series/{series}/posts/{post}', [\App\Http\Controllers\Admin\SeriesController::class, 'removePost'])->name('series.posts.remove');
    Route::put('/series/{series}/order', [\App\Http\Controllers\Admin\SeriesController::class, 'updateOrder'])->name('series.order.update');

    // Broken links routes
    Route::get('/broken-links', [\App\Http\Controllers\Admin\BrokenLinkController::class, 'index'])->name('broken-links.index');
    Route::patch('/broken-links/{brokenLink}/mark-fixed', [\App\Http\Controllers\Admin\BrokenLinkController::class, 'markAsFixed'])->name('broken-links.mark-fixed');
    Route::patch('/broken-links/{brokenLink}/mark-ignored', [\App\Http\Controllers\Admin\BrokenLinkController::class, 'markAsIgnored'])->name('broken-links.mark-ignored');
    Route::delete('/broken-links/{brokenLink}', [\App\Http\Controllers\Admin\BrokenLinkController::class, 'destroy'])->name('broken-links.destroy');
    Route::post('/broken-links/bulk-action', [\App\Http\Controllers\Admin\BrokenLinkController::class, 'bulkAction'])->name('broken-links.bulk-action');

    // Newsletter routes
    Route::get('/newsletters', [\App\Http\Controllers\Admin\NewsletterController::class, 'index'])->name('newsletters.index');
    Route::get('/newsletters/export', [\App\Http\Controllers\Admin\NewsletterController::class, 'export'])->name('newsletters.export');
});

require __DIR__.'/auth.php';
