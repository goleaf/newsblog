<?php

use App\Http\Controllers\Admin\MediaController as AdminMediaController;
use App\Http\Controllers\Admin\SearchController as AdminSearchController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PostController as PublicPostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\UiDemoController;
use Illuminate\Support\Facades\Route;

// Health check endpoints for monitoring
Route::get('/health', [HealthCheckController::class, 'index'])->name('health.index');
Route::get('/health/{component}', [HealthCheckController::class, 'component'])->name('health.component');
Route::get('/ping', [HealthCheckController::class, 'ping'])->name('health.ping');

Route::get('/', [HomeController::class, 'index'])->name('home');

// Editor's Picks ordering (no auth per project rules)
Route::get('/editors-picks', [\App\Http\Controllers\EditorsPicksController::class, 'index'])->name('editors-picks.index');
Route::post('/editors-picks/order', [\App\Http\Controllers\EditorsPicksController::class, 'updateOrder'])->name('editors-picks.order');

// Offline fallback page (for PWA)
Route::view('/offline', 'offline')->name('offline');

// Sitemap routes
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.index');
Route::get('/robots.txt', [RobotsController::class, 'index'])->name('robots.txt');
Route::get('/post/{slug}', [PublicPostController::class, 'show'])->name('post.show');

// Article routes (alias for posts)
Route::get('/articles', [\App\Http\Controllers\ArticleController::class, 'index'])->name('articles.index');

// Authenticated article management routes
Route::middleware(['auth'])->group(function () {
    Route::get('/articles/create', [\App\Http\Controllers\ArticleController::class, 'create'])->name('articles.create');
    Route::post('/articles', [\App\Http\Controllers\ArticleController::class, 'store'])->name('articles.store');
    Route::get('/articles/{article}/edit', [\App\Http\Controllers\ArticleController::class, 'edit'])->name('articles.edit');
    Route::put('/articles/{article}', [\App\Http\Controllers\ArticleController::class, 'update'])->name('articles.update');
    Route::delete('/articles/{article}', [\App\Http\Controllers\ArticleController::class, 'destroy'])->name('articles.destroy');
    Route::post('/articles/{article}/publish', [\App\Http\Controllers\ArticleController::class, 'publish'])->name('articles.publish');
    Route::post('/articles/{article}/unpublish', [\App\Http\Controllers\ArticleController::class, 'unpublish'])->name('articles.unpublish');
});

// Slug route must be defined after the create/edit routes to avoid conflicts
Route::get('/articles/{article:slug}', [\App\Http\Controllers\ArticleController::class, 'show'])->name('articles.show');

Route::get('/categories', [\App\Http\Controllers\CategoryController::class, 'index'])->name('categories.index');
Route::get('/category/{slug}', [\App\Http\Controllers\CategoryController::class, 'show'])->name('category.show');
Route::get('/tags', [\App\Http\Controllers\TagController::class, 'index'])->name('tags.index');
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

// Social sharing routes
Route::post('/posts/{post}/share', [\App\Http\Controllers\SocialShareController::class, 'track'])
    ->middleware('throttle:60,1')
    ->name('posts.share.track');
Route::get('/posts/{post}/share-urls', [\App\Http\Controllers\SocialShareController::class, 'getShareUrls'])
    ->name('posts.share.urls');
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

// Admin: Newsletters
Route::middleware(['auth', 'role:admin,editor'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/newsletters', [\App\Http\Controllers\Admin\NewsletterController::class, 'index'])->name('newsletters.index');
    Route::get('/newsletters/export', [\App\Http\Controllers\Admin\NewsletterController::class, 'export'])->name('newsletters.export');
    Route::get('/newsletters/sends', [\App\Http\Controllers\Admin\NewsletterController::class, 'sends'])->name('newsletters.sends');
    Route::get('/newsletters/sends/export', [\App\Http\Controllers\Admin\NewsletterController::class, 'exportSends'])->name('newsletters.sends.export');
    Route::get('/newsletters/sends/{send}', [\App\Http\Controllers\Admin\NewsletterController::class, 'showSend'])->name('newsletters.sends.show');
    Route::post('/newsletters/sends/{send}/resend', [\App\Http\Controllers\Admin\NewsletterController::class, 'resend'])->name('newsletters.sends.resend');

    // Newsletter admin interface
    Route::get('/newsletter/dashboard', [\App\Http\Controllers\Admin\NewsletterAdminController::class, 'index'])->name('newsletter.index');
    Route::get('/newsletter/preview', [\App\Http\Controllers\Admin\NewsletterAdminController::class, 'preview'])->name('newsletter.preview');
    Route::post('/newsletter/send', [\App\Http\Controllers\Admin\NewsletterAdminController::class, 'send'])->name('newsletter.send');
    Route::get('/newsletter/metrics/{batchId}', [\App\Http\Controllers\Admin\NewsletterAdminController::class, 'metrics'])->name('newsletter.metrics');
    Route::get('/newsletter/subscribers', [\App\Http\Controllers\Admin\NewsletterAdminController::class, 'subscribers'])->name('newsletter.subscribers');
});

// Minimal Nova JSON endpoints for tests (no Nova dependency)
Route::prefix('nova-api')
    ->withoutMiddleware([
        \Laravel\Nova\Http\Middleware\DispatchServingNovaEvent::class,
        \Laravel\Nova\Http\Middleware\HandleInertiaRequests::class,
    ])
    ->middleware(['auth', 'role:admin'])
    ->group(function () {
        Route::get('/posts', [\App\Http\Controllers\Nova\SearchController::class, 'posts']);
        Route::get('/users', [\App\Http\Controllers\Nova\SearchController::class, 'users']);
        Route::get('/categories', [\App\Http\Controllers\Nova\SearchController::class, 'categories']);
        Route::get('/tags', [\App\Http\Controllers\Nova\SearchController::class, 'tags']);
        Route::get('/comments', [\App\Http\Controllers\Nova\SearchController::class, 'comments']);
        Route::get('/media', [\App\Http\Controllers\Nova\SearchController::class, 'media']);
    });

// Reading List routes
Route::middleware('auth')->group(function () {
    Route::get('/reading-lists', [\App\Http\Controllers\ReadingListController::class, 'index'])->name('reading-lists.index');
    Route::get('/reading-lists/create', [\App\Http\Controllers\ReadingListController::class, 'create'])->name('reading-lists.create');
    Route::post('/reading-lists', [\App\Http\Controllers\ReadingListController::class, 'store'])->name('reading-lists.store');
    Route::get('/reading-lists/{collection}', [\App\Http\Controllers\ReadingListController::class, 'show'])->name('reading-lists.show');
    Route::get('/reading-lists/{collection}/edit', [\App\Http\Controllers\ReadingListController::class, 'edit'])->name('reading-lists.edit');
    Route::put('/reading-lists/{collection}', [\App\Http\Controllers\ReadingListController::class, 'update'])->name('reading-lists.update');
    Route::delete('/reading-lists/{collection}', [\App\Http\Controllers\ReadingListController::class, 'destroy'])->name('reading-lists.destroy');
    Route::post('/reading-lists/{collection}/items', [\App\Http\Controllers\ReadingListController::class, 'addItem'])->name('reading-lists.add-item');
    Route::delete('/reading-lists/{collection}/items/{bookmark}', [\App\Http\Controllers\ReadingListController::class, 'removeItem'])->name('reading-lists.remove-item');
    Route::post('/reading-lists/{collection}/reorder', [\App\Http\Controllers\ReadingListController::class, 'reorder'])->name('reading-lists.reorder');
    Route::post('/reading-lists/{collection}/share', [\App\Http\Controllers\ReadingListController::class, 'share'])->name('reading-lists.share');
    Route::delete('/reading-lists/{collection}/share', [\App\Http\Controllers\ReadingListController::class, 'revokeShare'])->name('reading-lists.revoke-share');
});

// Public shared reading list view
Route::get('/reading-lists/shared/{token}', [\App\Http\Controllers\ReadingListController::class, 'sharedShow'])->name('reading-lists.shared');

// Newsletter routes
Route::post('/newsletter/subscribe', [\App\Http\Controllers\NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
Route::get('/newsletter/verify/{token}', [\App\Http\Controllers\NewsletterController::class, 'verify'])->name('newsletter.verify');
Route::get('/newsletter/unsubscribe/{token}', [\App\Http\Controllers\NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');
Route::get('/newsletter/preferences/{token}', [\App\Http\Controllers\NewsletterController::class, 'showPreferences'])->name('newsletter.preferences');
Route::post('/newsletter/preferences/{token}', [\App\Http\Controllers\NewsletterController::class, 'updatePreferences'])->name('newsletter.preferences.update');

// Newsletter tracking routes
Route::get('/newsletter/track/open/{token}', [\App\Http\Controllers\NewsletterTrackingController::class, 'trackOpen'])->name('newsletter.track.open');
Route::get('/newsletter/track/click/{token}', [\App\Http\Controllers\NewsletterTrackingController::class, 'trackClick'])->name('newsletter.track.click');
Route::get('/newsletter/report/{batchId}', [\App\Http\Controllers\NewsletterTrackingController::class, 'engagementReport'])->name('newsletter.report');

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

// Public profile routes
Route::get('/users/{user}', [ProfileController::class, 'showPublic'])->name('users.show');
Route::get('/users/{user}/followers', [\App\Http\Controllers\FollowController::class, 'followers'])->name('users.followers');
Route::get('/users/{user}/following', [\App\Http\Controllers\FollowController::class, 'following'])->name('users.following');

Route::middleware('auth')->group(function () {
    // Follow routes
    Route::post('/users/{user}/follow', [\App\Http\Controllers\FollowController::class, 'follow'])->name('users.follow');
    Route::delete('/users/{user}/follow', [\App\Http\Controllers\FollowController::class, 'unfollow'])->name('users.unfollow');
    Route::get('/users/{user}/follow-status', [\App\Http\Controllers\FollowController::class, 'checkStatus'])->name('users.follow-status');

    // Activity feed routes
    Route::get('/activities', [\App\Http\Controllers\ActivityController::class, 'index'])->name('activities.index');
    Route::get('/activities/following', [\App\Http\Controllers\ActivityController::class, 'following'])->name('activities.following');
    Route::get('/activities/{activity}', [\App\Http\Controllers\ActivityController::class, 'show'])->name('activities.show');

    // Recommendation routes
    Route::get('/recommendations', [\App\Http\Controllers\RecommendationController::class, 'index'])->name('recommendations.index');
    Route::get('/posts/{post}/similar', [\App\Http\Controllers\RecommendationController::class, 'similar'])->name('recommendations.similar');
    Route::post('/recommendations/track-click', [\App\Http\Controllers\RecommendationController::class, 'trackClick'])
        ->middleware('throttle:60,1')
        ->name('recommendations.track-click');
    Route::post('/recommendations/track-impression', [\App\Http\Controllers\RecommendationController::class, 'trackImpression'])
        ->middleware('throttle:60,1')
        ->name('recommendations.track-impression');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/preferences', [ProfileController::class, 'updatePreferences'])->name('profile.preferences.update');
    Route::patch('/profile/email-preferences', [ProfileController::class, 'updateEmailPreferences'])->name('profile.email-preferences');
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar'])->name('profile.avatar.upload');
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
    Route::get('/notifications/count', [\App\Http\Controllers\NotificationController::class, 'count'])->name('notifications.count');
    Route::post('/notifications/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::delete('/notifications/{notification}', [\App\Http\Controllers\NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::delete('/notifications/read/delete-all', [\App\Http\Controllers\NotificationController::class, 'deleteRead'])->name('notifications.delete-read');
    Route::get('/notifications/preferences', [\App\Http\Controllers\NotificationController::class, 'preferences'])->name('notifications.preferences');
    Route::put('/notifications/preferences', [\App\Http\Controllers\NotificationController::class, 'updatePreferences'])->name('notifications.preferences.update');

    // GDPR authenticated routes
    Route::get('/gdpr/export-data', [\App\Http\Controllers\GdprController::class, 'exportData'])->name('gdpr.export-data');
    Route::get('/gdpr/delete-account', [\App\Http\Controllers\GdprController::class, 'showDeleteAccount'])->name('gdpr.show-delete-account');
    Route::delete('/gdpr/delete-account', [\App\Http\Controllers\GdprController::class, 'deleteAccount'])->name('gdpr.delete-account');
    Route::post('/gdpr/withdraw-consent', [\App\Http\Controllers\GdprController::class, 'withdrawConsent'])->name('gdpr.withdraw-consent');
});

// Public bookmarks (anonymous via reader_token)
Route::get('/bookmarks', [\App\Http\Controllers\BookmarkController::class, 'index'])->name('bookmarks.index');
Route::post('/bookmarks', [\App\Http\Controllers\BookmarkController::class, 'storeAnonymous'])->name('bookmarks.store.anonymous');
Route::delete('/bookmarks', [\App\Http\Controllers\BookmarkController::class, 'destroyAnonymous'])->name('bookmarks.destroy.anonymous');
Route::post('/bookmarks/toggle', [\App\Http\Controllers\BookmarkController::class, 'toggleAnonymous'])
    ->middleware('throttle:120,1')
    ->name('bookmarks.toggle.anonymous');

// Authenticated bookmark routes (user-based reading list)
Route::middleware('auth')->group(function () {
    Route::post('/bookmarks/{post}', [\App\Http\Controllers\BookmarkController::class, 'store'])->name('bookmarks.store');
    Route::delete('/bookmarks/{post}', [\App\Http\Controllers\BookmarkController::class, 'destroy'])->name('bookmarks.destroy');
    Route::post('/bookmarks/{post}/toggle', [\App\Http\Controllers\BookmarkController::class, 'toggle'])
        ->middleware('throttle:120,1')
        ->name('bookmarks.toggle');
    Route::post('/bookmarks/{bookmark}/read', [\App\Http\Controllers\BookmarkController::class, 'markAsRead'])->name('bookmarks.read');
    Route::post('/bookmarks/{bookmark}/unread', [\App\Http\Controllers\BookmarkController::class, 'markAsUnread'])->name('bookmarks.unread');
    Route::post('/bookmarks/{bookmark}/notes', [\App\Http\Controllers\BookmarkController::class, 'updateNotes'])->name('bookmarks.notes');
});

// Page routes (supports nested slugs via slugPath)
Route::get('/page/{slugPath}', [\App\Http\Controllers\PageController::class, 'show'])
    ->where('slugPath', '.*')
    ->name('page.show');
Route::post('/page/contact', [\App\Http\Controllers\PageController::class, 'submitContact'])
    ->middleware('throttle:comments')
    ->name('page.contact.submit');

// Admin routes
Route::middleware(['auth', 'role:admin,editor'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/analytics', [\App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('analytics');
    Route::get('/analytics/articles', [\App\Http\Controllers\Admin\AnalyticsController::class, 'articlePerformance'])->name('analytics.articles');
    Route::get('/analytics/traffic', [\App\Http\Controllers\Admin\AnalyticsController::class, 'trafficSources'])->name('analytics.traffic');
    Route::get('/analytics/engagement', [\App\Http\Controllers\Admin\AnalyticsController::class, 'userEngagement'])->name('analytics.engagement');
    Route::get('/analytics/export', [\App\Http\Controllers\Admin\AnalyticsController::class, 'export'])->name('analytics.export');
    Route::get('/search', [AdminSearchController::class, 'index'])->name('search');
    Route::get('/search/analytics', [AdminSearchController::class, 'analytics'])->name('search.analytics');
    Route::get('/recommendations/metrics', [\App\Http\Controllers\RecommendationController::class, 'metrics'])->name('recommendations.metrics');

    // Category management routes
    Route::resource('categories', \App\Http\Controllers\Admin\CategoryController::class);

    // Simple admin search/listing endpoints used by tests
    Route::get('/posts', [\App\Http\Controllers\Admin\PostController::class, 'index'])->name('posts.index');
    Route::get('/tags', [\App\Http\Controllers\Admin\TagController::class, 'index'])->name('tags.index');
    Route::get('/comments', [\App\Http\Controllers\Admin\CommentController::class, 'index'])->name('comments.index');
    Route::get('/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');

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
    Route::get('/calendar/export.ics', [\App\Http\Controllers\Admin\ContentCalendarController::class, 'exportIcs'])->name('calendar.export');

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

// Moderation routes (moderators and admins only)
Route::middleware(['auth', 'role:moderator,admin'])->prefix('moderation')->name('moderation.')->group(function () {
    Route::get('/', [\App\Http\Controllers\ModerationController::class, 'index'])->name('index');
    Route::get('/{moderationQueue}', [\App\Http\Controllers\ModerationController::class, 'show'])->name('show');
    Route::post('/{moderationQueue}/approve', [\App\Http\Controllers\ModerationController::class, 'approve'])->name('approve');
    Route::post('/{moderationQueue}/reject', [\App\Http\Controllers\ModerationController::class, 'reject'])->name('reject');
    Route::post('/{moderationQueue}/delete', [\App\Http\Controllers\ModerationController::class, 'delete'])->name('delete');
    Route::post('/bulk-action', [\App\Http\Controllers\ModerationController::class, 'bulkAction'])->name('bulk-action');
    Route::post('/users/{user}/ban', [\App\Http\Controllers\ModerationController::class, 'banUser'])->name('ban-user');
});

// Comment flagging routes (authenticated users)
Route::middleware('auth')->group(function () {
    Route::post('/comments/{comment}/flag', [\App\Http\Controllers\CommentFlagController::class, 'store'])->name('comments.flag');
});

// Comment flag management (moderators only)
Route::middleware(['auth', 'role:moderator,admin'])->group(function () {
    Route::get('/comments/{comment}/flags', [\App\Http\Controllers\CommentFlagController::class, 'index'])->name('comments.flags.index');
    Route::post('/flags/{flag}/dismiss', [\App\Http\Controllers\CommentFlagController::class, 'dismiss'])->name('flags.dismiss');
    Route::post('/flags/{flag}/resolve', [\App\Http\Controllers\CommentFlagController::class, 'resolve'])->name('flags.resolve');
    Route::get('/moderation/flag-statistics', [\App\Http\Controllers\CommentFlagController::class, 'statistics'])->name('moderation.flag-statistics');
});

// Public UI demo (no auth)
Route::get('/ui-demo', [UiDemoController::class, 'show'])->name('ui.demo');
