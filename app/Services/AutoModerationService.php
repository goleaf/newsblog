<?php

namespace App\Services;

use App\Enums\CommentStatus;
use App\Enums\ModerationReason;
use App\Models\Comment;
use App\Models\User;
use App\Models\UserReputation;
use Illuminate\Support\Facades\Log;

class AutoModerationService
{
    /**
     * Prohibited words and phrases that trigger automatic flagging.
     */
    protected array $prohibitedWords = [
        'viagra',
        'cialis',
        'casino',
        'poker',
        'lottery',
        'buy now',
        'click here',
        'limited time',
        'act now',
        'free money',
        'get rich',
        'work from home',
        'weight loss',
        'crypto scam',
    ];

    /**
     * Spam patterns that trigger automatic flagging.
     */
    protected array $spamPatterns = [
        '/\b(buy|cheap|discount|sale)\b.*\b(now|today|limited)\b/i',
        '/(https?:\/\/[^\s]+){3,}/i', // Multiple links
        '/\b[A-Z]{10,}\b/', // Excessive caps
        '/(.)\1{10,}/', // Repeated characters
        '/\$\d+/i', // Money amounts
    ];

    /**
     * Check if content should be flagged for moderation.
     */
    public function shouldFlag(string $content, User $user): array
    {
        $flags = [];

        // Check prohibited words
        if ($this->containsProhibitedWords($content)) {
            $flags[] = ModerationReason::ProhibitedContent;
        }

        // Check spam patterns
        if ($this->matchesSpamPatterns($content)) {
            $flags[] = ModerationReason::Spam;
        }

        // Check user reputation
        if ($this->hasLowReputation($user)) {
            $flags[] = ModerationReason::LowQuality;
        }

        // Check for excessive links
        if ($this->hasExcessiveLinks($content)) {
            $flags[] = ModerationReason::Spam;
        }

        // Check for offensive content
        if ($this->containsOffensiveContent($content)) {
            $flags[] = ModerationReason::Offensive;
        }

        // Remove duplicates by converting to array of values and back
        $uniqueFlags = [];
        $seen = [];
        foreach ($flags as $flag) {
            $value = $flag->value;
            if (! in_array($value, $seen)) {
                $uniqueFlags[] = $flag;
                $seen[] = $value;
            }
        }

        return $uniqueFlags;
    }

    /**
     * Moderate a comment automatically.
     */
    public function moderateComment(Comment $comment): CommentStatus
    {
        $flags = $this->shouldFlag($comment->content, $comment->user);

        if (empty($flags)) {
            // Check if user is trusted
            if ($this->isTrustedUser($comment->user)) {
                return CommentStatus::Approved;
            }

            return CommentStatus::Pending;
        }

        // Check if any flag should result in automatic rejection
        foreach ($flags as $flag) {
            if ($flag->shouldAutoReject()) {
                Log::channel('security')->warning('Comment auto-rejected', [
                    'comment_id' => $comment->id,
                    'user_id' => $comment->user_id,
                    'reasons' => array_map(fn ($f) => $f->value, $flags),
                ]);

                return CommentStatus::Rejected;
            }
        }

        // Flag for manual review
        Log::info('Comment flagged for moderation', [
            'comment_id' => $comment->id,
            'user_id' => $comment->user_id,
            'reasons' => array_map(fn ($f) => $f->value, $flags),
        ]);

        return CommentStatus::Flagged;
    }

    /**
     * Check if content contains prohibited words.
     */
    protected function containsProhibitedWords(string $content): bool
    {
        $lowerContent = strtolower($content);

        foreach ($this->prohibitedWords as $word) {
            if (str_contains($lowerContent, strtolower($word))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if content matches spam patterns.
     */
    protected function matchesSpamPatterns(string $content): bool
    {
        foreach ($this->spamPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has low reputation.
     */
    protected function hasLowReputation(User $user): bool
    {
        $reputation = UserReputation::where('user_id', $user->id)->first();

        if (! $reputation) {
            // New users have no reputation yet
            return true;
        }

        // Consider low reputation if score is below 10 or level is 'new'
        return $reputation->score < 10 || $reputation->level === 'new';
    }

    /**
     * Check if content has excessive links.
     */
    protected function hasExcessiveLinks(string $content): bool
    {
        $linkCount = preg_match_all('/https?:\/\/[^\s]+/i', $content);

        return $linkCount > 2;
    }

    /**
     * Check if content contains offensive language.
     */
    protected function containsOffensiveContent(string $content): bool
    {
        // Basic offensive word detection
        // In production, this should use a more sophisticated service
        $offensiveWords = [
            // Add offensive words here
            // This is a placeholder - actual implementation should use a proper service
        ];

        $lowerContent = strtolower($content);

        foreach ($offensiveWords as $word) {
            if (str_contains($lowerContent, strtolower($word))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user is trusted based on reputation.
     */
    protected function isTrustedUser(User $user): bool
    {
        $reputation = UserReputation::where('user_id', $user->id)->first();

        if (! $reputation) {
            return false;
        }

        // Trusted users have high reputation score and level
        return $reputation->score >= 50 && in_array($reputation->level, ['trusted', 'expert']);
    }

    /**
     * Calculate spam score for content (0-100).
     */
    public function calculateSpamScore(string $content, User $user): int
    {
        $score = 0;

        // Check prohibited words (20 points)
        if ($this->containsProhibitedWords($content)) {
            $score += 20;
        }

        // Check spam patterns (30 points)
        if ($this->matchesSpamPatterns($content)) {
            $score += 30;
        }

        // Check excessive links (15 points)
        if ($this->hasExcessiveLinks($content)) {
            $score += 15;
        }

        // Check offensive content (25 points)
        if ($this->containsOffensiveContent($content)) {
            $score += 25;
        }

        // Check user reputation (10 points)
        if ($this->hasLowReputation($user)) {
            $score += 10;
        }

        return min($score, 100);
    }

    /**
     * Get detailed moderation analysis.
     */
    public function analyzeContent(string $content, User $user): array
    {
        return [
            'spam_score' => $this->calculateSpamScore($content, $user),
            'flags' => $this->shouldFlag($content, $user),
            'has_prohibited_words' => $this->containsProhibitedWords($content),
            'matches_spam_patterns' => $this->matchesSpamPatterns($content),
            'has_excessive_links' => $this->hasExcessiveLinks($content),
            'contains_offensive_content' => $this->containsOffensiveContent($content),
            'user_reputation_low' => $this->hasLowReputation($user),
            'user_trusted' => $this->isTrustedUser($user),
        ];
    }
}
