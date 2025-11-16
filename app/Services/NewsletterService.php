<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Str;

class NewsletterService
{
    /**
     * Generate a simple HTML digest for a newsletter period.
     */
    public function generateDigest(string $period = 'daily', int $limit = 5): string
    {
        $query = Post::published()->with(['user:id,name', 'category:id,name,slug']);

        // Pick time window/scoring
        match ($period) {
            'weekly' => $query->where('published_at', '>=', now()->subWeek()),
            'monthly' => $query->where('published_at', '>=', now()->subMonth()),
            default => $query->where('published_at', '>=', now()->subDay()),
        };

        $posts = $query->orderByDesc('view_count')->orderByDesc('published_at')->limit($limit)->get();

        if ($posts->isEmpty()) {
            $posts = Post::published()->latest('published_at')->limit($limit)->get();
        }

        $items = $posts->map(function (Post $post) {
            $url = route('post.show', $post->slug);
            $title = e($post->title);
            $excerpt = e(Str::limit(strip_tags((string) $post->excerpt ?: (string) $post->content), 180));
            $author = e($post->user?->name ?? '');
            $date = $post->published_at?->format('M d, Y');

            return '<tr><td style="padding:12px 0;border-bottom:1px solid #eee;">'
                ."<a href=\"{$url}\" style=\"font-size:18px;color:#111;text-decoration:none;\">{$title}</a>"
                ."<div style=\"font-size:13px;color:#666;margin-top:4px;\">{$excerpt}</div>"
                ."<div style=\"font-size:12px;color:#999;margin-top:6px;\">{$author} · {$date}</div>"
                .'</td></tr>';
        })->implode('');

        $heading = match ($period) {
            'weekly' => 'Your Weekly Tech Digest',
            'monthly' => 'Your Monthly Tech Digest',
            default => 'Today’s Top Stories',
        };

        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-family:Arial,Helvetica,sans-serif;">'
            ."<tr><td><h1 style=\"font-size:22px;margin:0 0 16px;\">{$heading}</h1></td></tr>"
            .$items
            .'</table>';
    }
}
