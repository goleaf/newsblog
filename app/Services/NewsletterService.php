<?php

namespace App\Services;

use App\Models\Newsletter;
use App\Models\Post;
use Illuminate\Support\Collection;

class NewsletterService
{
    /**
     * Generate newsletter content based on top articles by engagement.
     */
    public function generateNewsletterContent(string $frequency = 'weekly', int $limit = 10): array
    {
        $days = $this->getDaysForFrequency($frequency);
        $topArticles = $this->selectTopArticlesByEngagement($days, $limit);

        return [
            'subject' => $this->generateSubject($frequency),
            'articles' => $topArticles,
            'generated_at' => now(),
        ];
    }

    /**
     * Select top articles by engagement metrics.
     */
    public function selectTopArticlesByEngagement(int $days, int $limit = 10): Collection
    {
        $startDate = now()->subDays($days);

        return Post::published()
            ->where('published_at', '>=', $startDate)
            ->withCount([
                'views as views_count' => function ($query) use ($startDate) {
                    $query->where('created_at', '>=', $startDate);
                },
                'comments as comments_count',
                'bookmarks as bookmarks_count',
                'reactions as reactions_count',
                'socialShares as shares_count',
            ])
            ->with(['user:id,name,email', 'category:id,name,slug', 'tags:id,name'])
            ->get()
            ->map(function ($post) {
                // Calculate engagement score
                $post->engagement_score = $this->calculateEngagementScore($post);

                return $post;
            })
            ->sortByDesc('engagement_score')
            ->take($limit)
            ->values();
    }

    /**
     * Calculate engagement score for an article.
     */
    protected function calculateEngagementScore($post): float
    {
        // Weighted scoring system
        $viewsWeight = 1;
        $commentsWeight = 5;
        $bookmarksWeight = 3;
        $reactionsWeight = 2;
        $sharesWeight = 4;

        $score = ($post->views_count * $viewsWeight)
            + ($post->comments_count * $commentsWeight)
            + ($post->bookmarks_count * $bookmarksWeight)
            + ($post->reactions_count * $reactionsWeight)
            + ($post->shares_count * $sharesWeight);

        // Boost recent articles (recency factor)
        $daysOld = now()->diffInDays($post->published_at);
        $recencyBoost = max(0, 1 - ($daysOld / 7)); // Decreases over 7 days
        $score *= (1 + $recencyBoost);

        return round($score, 2);
    }

    /**
     * Build HTML email template for newsletter.
     */
    public function buildHtmlTemplate(array $content, Newsletter $subscriber): string
    {
        $articles = $content['articles'];
        $subject = $content['subject'];

        return view('emails.newsletter', [
            'subject' => $subject,
            'articles' => $articles,
            'subscriber' => $subscriber,
            'unsubscribeUrl' => route('newsletter.unsubscribe', $subscriber->unsubscribe_token),
            'preferencesUrl' => route('newsletter.preferences', $subscriber->unsubscribe_token),
        ])->render();
    }

    /**
     * Personalize content per subscriber.
     */
    public function personalizeContent(array $content, Newsletter $subscriber): array
    {
        // Add personalization data
        $content['subscriber_email'] = $subscriber->email;
        $content['subscriber_frequency'] = $subscriber->frequency;
        $content['greeting'] = $this->generateGreeting($subscriber);

        // Filter articles based on subscriber preferences if available
        // For now, we use the same articles for all subscribers
        // Future enhancement: filter by subscriber's reading history or preferences

        return $content;
    }

    /**
     * Generate personalized greeting.
     */
    protected function generateGreeting(Newsletter $subscriber): string
    {
        $hour = now()->hour;

        if ($hour < 12) {
            $timeGreeting = 'Good morning';
        } elseif ($hour < 18) {
            $timeGreeting = 'Good afternoon';
        } else {
            $timeGreeting = 'Good evening';
        }

        // Extract name from email if available
        $emailParts = explode('@', $subscriber->email);
        $name = ucfirst($emailParts[0]);

        return "{$timeGreeting}, {$name}!";
    }

    /**
     * Generate newsletter subject line.
     */
    protected function generateSubject(string $frequency): string
    {
        $date = now()->format('F j, Y');

        return match ($frequency) {
            'daily' => "Your Daily Tech Digest - {$date}",
            'weekly' => "This Week in Tech - {$date}",
            'monthly' => 'Monthly Tech Roundup - '.now()->format('F Y'),
            default => "Tech News Update - {$date}",
        };
    }

    /**
     * Get number of days to look back based on frequency.
     */
    protected function getDaysForFrequency(string $frequency): int
    {
        return match ($frequency) {
            'daily' => 1,
            'weekly' => 7,
            'monthly' => 30,
            default => 7,
        };
    }

    /**
     * Get subscribers eligible for newsletter based on frequency.
     */
    public function getEligibleSubscribers(string $frequency): Collection
    {
        return Newsletter::subscribed()
            ->verified()
            ->where('frequency', $frequency)
            ->get();
    }

    /**
     * Get all unique frequencies from active subscribers.
     */
    public function getActiveFrequencies(): array
    {
        return Newsletter::subscribed()
            ->verified()
            ->distinct()
            ->pluck('frequency')
            ->filter()
            ->toArray();
    }
}
