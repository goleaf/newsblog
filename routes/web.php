<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PostController as PublicPostController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/post/{slug}', [PublicPostController::class, 'show'])->name('post.show');
Route::get('/category/{slug}', [PublicPostController::class, 'category'])->name('category.show');
Route::get('/tag/{slug}', [PublicPostController::class, 'tag'])->name('tag.show');
Route::get('/search', [PublicPostController::class, 'search'])
    ->middleware('throttle:search')
    ->name('search');
Route::post('/comments', [CommentController::class, 'store'])
    ->middleware('throttle:comments')
    ->name('comments.store');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Deprecated admin routes - redirecting to Nova equivalents
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    // Dashboard redirect - redirect to Nova's main dashboard
    // Note: /admin is already Nova's path, so we redirect to a specific resource to avoid loop
    Route::get('/', function () {
        return redirect('/admin/resources/posts')->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('dashboard');

    // Posts resource redirects
    Route::get('posts', function () {
        return redirect('/admin/resources/posts')->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('posts.index');
    Route::get('posts/create', function () {
        return redirect('/admin/resources/posts/new')->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('posts.create');
    Route::get('posts/{id}', function ($id) {
        return redirect("/admin/resources/posts/{$id}")->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('posts.show');
    Route::get('posts/{id}/edit', function ($id) {
        return redirect("/admin/resources/posts/{$id}/edit")->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('posts.edit');
    Route::post('posts', function () {
        return redirect('/admin/resources/posts')->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('posts.store');
    Route::put('posts/{id}', function ($id) {
        return redirect("/admin/resources/posts/{$id}")->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('posts.update');
    Route::delete('posts/{id}', function ($id) {
        return redirect('/admin/resources/posts')->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('posts.destroy');

    // Categories resource redirects
    Route::get('categories', function () {
        return redirect('/admin/resources/categories')->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('categories.index');
    Route::get('categories/create', function () {
        return redirect('/admin/resources/categories/new')->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('categories.create');
    Route::get('categories/{id}', function ($id) {
        return redirect("/admin/resources/categories/{$id}")->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('categories.show');
    Route::get('categories/{id}/edit', function ($id) {
        return redirect("/admin/resources/categories/{$id}/edit")->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('categories.edit');
    Route::post('categories', function () {
        return redirect('/admin/resources/categories')->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('categories.store');
    Route::put('categories/{id}', function ($id) {
        return redirect("/admin/resources/categories/{$id}")->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('categories.update');
    Route::delete('categories/{id}', function ($id) {
        return redirect('/admin/resources/categories')->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('categories.destroy');

    // Tags resource redirects
    Route::get('tags', function () {
        return redirect('/admin/resources/tags')->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('tags.index');
    Route::post('tags', function () {
        return redirect('/admin/resources/tags')->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('tags.store');
    Route::put('tags/{id}', function ($id) {
        return redirect('/admin/resources/tags')->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('tags.update');
    Route::delete('tags/{id}', function ($id) {
        return redirect('/admin/resources/tags')->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('tags.destroy');

    // Comments redirects
    Route::get('comments', function () {
        return redirect('/admin/resources/comments')->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('comments.index');
    Route::post('comments/{comment}/approve', function ($comment) {
        return redirect("/admin/resources/comments/{$comment}")->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('comments.approve');
    Route::post('comments/{comment}/spam', function ($comment) {
        return redirect("/admin/resources/comments/{$comment}")->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('comments.spam');
    Route::delete('comments/{comment}', function ($comment) {
        return redirect('/admin/resources/comments')->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('comments.destroy');

    // Media redirects
    Route::get('media', function () {
        return redirect('/admin/resources/media')->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('media.index');
    Route::post('media', function () {
        return redirect('/admin/resources/media')->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('media.store');
    Route::delete('media/{media}', function ($media) {
        return redirect('/admin/resources/media')->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('media.destroy');

    // Users resource redirects
    Route::get('users', function () {
        return redirect('/admin/resources/users')->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('users.index');
    Route::post('users', function () {
        return redirect('/admin/resources/users')->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('users.store');
    Route::put('users/{id}', function ($id) {
        return redirect('/admin/resources/users')->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('users.update');
    Route::delete('users/{id}', function ($id) {
        return redirect('/admin/resources/users')->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('users.destroy');

    // Pages resource redirects
    Route::get('pages', function () {
        return redirect('/admin/resources/pages')->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('pages.index');
    Route::get('pages/create', function () {
        return redirect('/admin/resources/pages/new')->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('pages.create');
    Route::get('pages/{id}/edit', function ($id) {
        return redirect("/admin/resources/pages/{$id}/edit")->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('pages.edit');
    Route::post('pages', function () {
        return redirect('/admin/resources/pages')->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('pages.store');
    Route::put('pages/{id}', function ($id) {
        return redirect("/admin/resources/pages/{$id}")->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('pages.update');
    Route::delete('pages/{id}', function ($id) {
        return redirect('/admin/resources/pages')->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('pages.destroy');

    // Newsletters redirects
    Route::get('newsletters', function () {
        return redirect('/admin/resources/newsletters')->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('newsletters.index');
    Route::delete('newsletters/{newsletter}', function ($newsletter) {
        return redirect('/admin/resources/newsletters')->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('newsletters.destroy');

    // Settings redirects
    Route::get('settings', function () {
        return redirect('/admin')->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('settings.index');
    Route::put('settings', function () {
        return redirect('/admin')->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('settings.update');

    // Activity logs redirects
    Route::get('activity-logs', function () {
        return redirect('/admin/resources/action-events')->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('activity-logs.index');
    Route::get('activity-logs/{activityLog}', function ($activityLog) {
        return redirect("/admin/resources/action-events/{$activityLog}")->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('activity-logs.show');

    // Maintenance redirects
    Route::get('maintenance', function () {
        return redirect('/admin')->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('maintenance.index');
    Route::post('maintenance/toggle', function () {
        return redirect('/admin')->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('maintenance.toggle');

    // Admin search redirects
    Route::get('search', function () {
        return redirect('/admin')->with('deprecated', 'This admin URL has been deprecated. Nova has built-in search functionality.');
    })->name('search.index');
    Route::get('search/analytics', function () {
        return redirect('/admin')->with('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');
    })->name('search.analytics');
});

require __DIR__.'/auth.php';
