<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\SocialShare;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SocialShareController extends Controller
{
    /**
     * Track a share event and increment share counter
     */
    public function track(Request $request, Post $post): JsonResponse
    {
        $validated = $request->validate([
            'platform' => 'required|in:twitter,facebook,linkedin,reddit,hackernews',
        ]);

        // Create share record
        SocialShare::create([
            'post_id' => $post->id,
            'user_id' => Auth::id(),
            'provider' => $validated['platform'],
            'shared_at' => now(),
        ]);

        // Get updated share count
        $shareCount = $post->socialShares()->count();

        return response()->json([
            'success' => true,
            'share_count' => $shareCount,
            'message' => 'Share tracked successfully',
        ]);
    }

    /**
     * Generate share URLs for different platforms
     */
    public function getShareUrls(Post $post): JsonResponse
    {
        $url = route('post.show', $post->slug);
        $title = $post->title;
        $excerpt = $post->excerpt ?? '';

        $shareUrls = [
            'twitter' => $this->generateTwitterUrl($url, $title),
            'facebook' => $this->generateFacebookUrl($url),
            'linkedin' => $this->generateLinkedInUrl($url, $title, $excerpt),
            'reddit' => $this->generateRedditUrl($url, $title),
            'hackernews' => $this->generateHackerNewsUrl($url, $title),
        ];

        return response()->json([
            'urls' => $shareUrls,
            'share_count' => $post->socialShares()->count(),
        ]);
    }

    /**
     * Generate Twitter share URL
     */
    private function generateTwitterUrl(string $url, string $title): string
    {
        return 'https://twitter.com/intent/tweet?'.http_build_query([
            'url' => $url,
            'text' => $title,
        ]);
    }

    /**
     * Generate Facebook share URL
     */
    private function generateFacebookUrl(string $url): string
    {
        return 'https://www.facebook.com/sharer/sharer.php?'.http_build_query([
            'u' => $url,
        ]);
    }

    /**
     * Generate LinkedIn share URL
     */
    private function generateLinkedInUrl(string $url, string $title, string $excerpt): string
    {
        return 'https://www.linkedin.com/sharing/share-offsite/?'.http_build_query([
            'url' => $url,
        ]);
    }

    /**
     * Generate Reddit share URL
     */
    private function generateRedditUrl(string $url, string $title): string
    {
        return 'https://www.reddit.com/submit?'.http_build_query([
            'url' => $url,
            'title' => $title,
        ]);
    }

    /**
     * Generate Hacker News share URL
     */
    private function generateHackerNewsUrl(string $url, string $title): string
    {
        return 'https://news.ycombinator.com/submitlink?'.http_build_query([
            'u' => $url,
            't' => $title,
        ]);
    }
}
