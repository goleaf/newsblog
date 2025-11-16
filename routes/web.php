<?php

use App\Http\Controllers\Admin\MediaController as AdminMediaController;
use App\Http\Controllers\Admin\SearchController as AdminSearchController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PostController as PublicPostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\RobotsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Offline fallback page (for PWA)
Route::view('/offline', 'offline')->name('offline');

// Sitemap routes
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.index');
Route::get('/robots.txt', [RobotsController::class, 'index'])->name('robots.txt');
Route::get('/post/{slug}', [PublicPostController::class, 'show'])->name('post.show');
Route::get('/category/{slug}', [\App\Http\Controllers\CategoryController::class, 'show'])->name('category.show');
Route::get('/tag/{slug}', [\App\Http\Controllers\TagController::class, 'show'])->name('tag.show');
Route::get('/series', [\App\Http\Controllers\SeriesController::class, 'index'])->name('series.index');
Route::get('/series/{slug}', [\App\Http\Controllers\SeriesController::class, 'show'])->name('series.show');
Route::get('/search', [SearchController::class, 'index'])
    ->middleware('throttle:search')
    ->name('search');
Route::get('/search/suggestions', [SearchController::class, 'suggestions'])
    ->middleware('throttle:search')
    ->name('search.suggestions');
Route::post('/search/track-click', [\App\Http\Controllers\SearchClickController::class, 'track'])
    ->middleware('throttle:60,1')
    ->name('search.track-click');
Route::post('/engagement/track', [\App\Http\Controllers\EngagementMetricController::class, 'track'])
    ->middleware('throttle:60,1')
    ->name('engagement.track');
Route::post('/comments', [CommentController::class, 'store'])
    ->middleware('throttle:comments')
    ->name('comments.store');

Route::post('/comments/reply', [CommentController::class, 'reply'])
    ->middleware('throttle:comments')
    ->name('comments.reply');

Route::middleware(['auth', 'role:admin,editor'])->group(function () {
    Route::post('/comments/{comment}/approve', [CommentController::class, 'approve'])
        ->name('comments.approve');

    Route::post('/comments/{comment}/reject', [CommentController::class, 'reject'])
        ->name('comments.reject');
});

Route::middleware('auth')->group(function () {
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])
        ->name('comments.destroy');
});

// Newsletter routes
Route::post('/newsletter/subscribe', [\App\Http\Controllers\NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
Route::get('/newsletter/verify/{token}', [\App\Http\Controllers\NewsletterController::class, 'verify'])->name('newsletter.verify');
Route::get('/newsletter/unsubscribe/{token}', [\App\Http\Controllers\NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// GDPR routes
Route::get('/privacy-policy', [\App\Http\Controllers\GdprController::class, 'privacyPolicy'])->name('gdpr.privacy-policy');
Route::get('/privacy-settings', function () {
    return view('gdpr.privacy-settings');
})->name('gdpr.privacy-settings');
Route::post('/gdpr/accept-consent', [\App\Http\Controllers\GdprController::class, 'acceptConsent'])->name('gdpr.accept-consent');
Route::post('/gdpr/decline-consent', [\App\Http\Controllers\GdprController::class, 'declineConsent'])->name('gdpr.decline-consent');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/email-preferences', [ProfileController::class, 'updateEmailPreferences'])->name('profile.email-preferences');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // (auth-specific features continue below)

    // Bookmark Collections
    Route::post('/bookmarks/collections', [\App\Http\Controllers\BookmarkCollectionController::class, 'store'])->name('bookmarks.collections.store');
    Route::get('/bookmarks/collections/{collection}', [\App\Http\Controllers\BookmarkCollectionController::class, 'show'])->name('bookmarks.collection');
    Route::put('/bookmarks/collections/{collection}', [\App\Http\Controllers\BookmarkCollectionController::class, 'update'])->name('bookmarks.collections.update');
    Route::delete('/bookmarks/collections/{collection}', [\App\Http\Controllers\BookmarkCollectionController::class, 'destroy'])->name('bookmarks.collections.destroy');
    Route::post('/bookmarks/collections/{collection}/reorder', [\App\Http\Controllers\BookmarkCollectionController::class, 'reorder'])->name('bookmarks.collections.reorder');
    Route::post('/bookmarks/{bookmark}/move', [\App\Http\Controllers\BookmarkCollectionController::class, 'moveBookmark'])->name('bookmarks.move');

    // Notifications
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread', [\App\Http\Controllers\NotificationController::class, 'unread'])->name('notifications.unread');
    Route::post('/notifications/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::delete('/notifications/{notification}', [\App\Http\Controllers\NotificationController::class, 'destroy'])->name('notifications.destroy');

    // GDPR authenticated routes
    Route::get('/gdpr/export-data', [\App\Http\Controllers\GdprController::class, 'exportData'])->name('gdpr.export-data');
    Route::get('/gdpr/delete-account', [\App\Http\Controllers\GdprController::class, 'showDeleteAccount'])->name('gdpr.show-delete-account');
    Route::delete('/gdpr/delete-account', [\App\Http\Controllers\GdprController::class, 'deleteAccount'])->name('gdpr.delete-account');
    Route::post('/gdpr/withdraw-consent', [\App\Http\Controllers\GdprController::class, 'withdrawConsent'])->name('gdpr.withdraw-consent');
});

// Public bookmark routes (anonymous via reader_token)
Route::get('/bookmarks', [\App\Http\Controllers\BookmarkController::class, 'index'])->name('bookmarks.index');
Route::post('/bookmarks', [\App\Http\Controllers\BookmarkController::class, 'store'])->name('bookmarks.store');
Route::delete('/bookmarks', [\App\Http\Controllers\BookmarkController::class, 'destroy'])->name('bookmarks.destroy');
Route::post('/bookmarks/toggle', [\App\Http\Controllers\BookmarkController::class, 'toggle'])
    ->middleware('throttle:120,1')
    ->name('bookmarks.toggle');

// Page routes
Route::get('/page/{slug}', [\App\Http\Controllers\PageController::class, 'show'])->name('page.show');
Route::post('/page/contact', [\App\Http\Controllers\PageController::class, 'submitContact'])
    ->middleware('throttle:comments')
    ->name('page.contact.submit');

// Admin routes
Route::middleware(['auth', 'role:admin,editor'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/analytics', [\App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('analytics');
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

    // Content Calendar routes
    Route::get('/calendar', [\App\Http\Controllers\Admin\ContentCalendarController::class, 'index'])->name('calendar.index');
    Route::get('/calendar/posts', [\App\Http\Controllers\Admin\ContentCalendarController::class, 'getPostsForDate'])->name('calendar.posts');
    Route::post('/calendar/posts/{post}/update-date', [\App\Http\Controllers\Admin\ContentCalendarController::class, 'updatePostDate'])->name('calendar.posts.update-date');

    // Widget Management routes
    Route::get('/widgets', [\App\Http\Controllers\Admin\WidgetController::class, 'index'])->name('widgets.index');
    Route::post('/widgets', [\App\Http\Controllers\Admin\WidgetController::class, 'store'])->name('widgets.store');
    Route::put('/widgets/{widget}', [\App\Http\Controllers\Admin\WidgetController::class, 'update'])->name('widgets.update');
    Route::delete('/widgets/{widget}', [\App\Http\Controllers\Admin\WidgetController::class, 'destroy'])->name('widgets.destroy');
    Route::post('/widgets/reorder', [\App\Http\Controllers\Admin\WidgetController::class, 'reorder'])->name('widgets.reorder');
    Route::post('/widgets/{widget}/toggle', [\App\Http\Controllers\Admin\WidgetController::class, 'toggle'])->name('widgets.toggle');

    // Alt Text Validation routes
    Route::get('/alt-text/report', [\App\Http\Controllers\Admin\AltTextController::class, 'report'])->name('alt-text.report');
    Route::get('/alt-text/bulk-edit', [\App\Http\Controllers\Admin\AltTextController::class, 'bulkEdit'])->name('alt-text.bulk-edit');
    Route::post('/alt-text/bulk-update', [\App\Http\Controllers\Admin\AltTextController::class, 'bulkUpdate'])->name('alt-text.bulk-update');
    Route::get('/posts/{post}/validate-alt-text', [\App\Http\Controllers\Admin\AltTextController::class, 'validatePost'])->name('posts.validate-alt-text');

    // Page Management routes
    Route::resource('pages', \App\Http\Controllers\Admin\PageController::class);
    Route::post('/pages/update-order', [\App\Http\Controllers\Admin\PageController::class, 'updateOrder'])->name('pages.update-order');

    // Settings Management routes
    Route::get('/settings', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [\App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/test-email', [\App\Http\Controllers\Admin\SettingsController::class, 'sendTestEmail'])->name('settings.test-email');
    Route::post('/settings/clear-cache', [\App\Http\Controllers\Admin\SettingsController::class, 'clearCache'])->name('settings.clear-cache');

    // Maintenance Mode routes
    Route::get('/maintenance', [\App\Http\Controllers\Admin\MaintenanceController::class, 'index'])->name('maintenance.index');
    Route::post('/maintenance/enable', [\App\Http\Controllers\Admin\MaintenanceController::class, 'enable'])->name('maintenance.enable');
    Route::post('/maintenance/disable', [\App\Http\Controllers\Admin\MaintenanceController::class, 'disable'])->name('maintenance.disable');
    Route::post('/maintenance/update', [\App\Http\Controllers\Admin\MaintenanceController::class, 'update'])->name('maintenance.update');
    Route::get('/maintenance/status', [\App\Http\Controllers\Admin\MaintenanceController::class, 'status'])->name('maintenance.status');
    Route::post('/maintenance/regenerate-secret', [\App\Http\Controllers\Admin\MaintenanceController::class, 'regenerateSecret'])->name('maintenance.regenerate-secret');

    // Monitoring routes
    Route::get('/monitoring', [\App\Http\Controllers\Admin\MonitoringController::class, 'index'])->name('monitoring.index');
    Route::post('/monitoring/reset', [\App\Http\Controllers\Admin\MonitoringController::class, 'reset'])->name('monitoring.reset');

    // Media library routes
    Route::get('/media', [AdminMediaController::class, 'index'])->name('media.index');
    Route::post('/media', [AdminMediaController::class, 'store'])->name('media.store');
    Route::delete('/media/{media}', [AdminMediaController::class, 'destroy'])->name('media.destroy');
    Route::get('/media/search', [AdminMediaController::class, 'search'])->name('media.search');
});

require __DIR__.'/auth.php';
