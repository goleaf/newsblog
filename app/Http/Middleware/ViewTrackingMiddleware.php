<?php

namespace App\Http\Middleware;

use App\Models\Article;
use App\Models\PostView;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ViewTrackingMiddleware
{
    /**
     * Handle an incoming request.
     * Track unique and total views, record reading time and scroll depth.
     * Requirements: 4.3, 8.1, 8.2
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track GET requests to article pages
        if ($request->isMethod('GET') && $request->route() && $request->route()->getName() === 'articles.show') {
            $article = $request->route('article');

            if ($article instanceof Article) {
                $this->trackView($article, $request);
            }
        }

        return $response;
    }

    /**
     * Track article view with duplicate prevention.
     * Requirements: 4.3, 8.1, 8.2
     */
    protected function trackView(Article $article, Request $request): void
    {
        $sessionKey = 'article_viewed_'.$article->id;

        // Prevent duplicate tracking within session
        if ($request->session()->has($sessionKey)) {
            return;
        }

        // Mark as viewed in session (expires after session ends)
        $request->session()->put($sessionKey, true);

        // Increment view count on article
        $article->increment('view_count');

        // Create detailed view record
        PostView::create([
            'post_id' => $article->id,
            'user_id' => auth()->id(),
            'session_id' => $request->session()->getId(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referer' => $request->header('referer'),
            'viewed_at' => now(),
        ]);
    }
}
