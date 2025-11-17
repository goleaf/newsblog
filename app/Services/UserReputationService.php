<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\User;
use App\Models\UserReputation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserReputationService
{
    /**
     * Calculate and update user reputation score.
     */
    public function calculateReputation(User $user): UserReputation
    {
        $reputation = UserReputation::firstOrCreate(
            ['user_id' => $user->id],
            [
                'score' => 0,
                'level' => 'new',
                'meta' => $this->getDefaultMeta(),
            ]
        );

        // Calculate score based on various factors
        $score = $this->calculateScore($user);

        // Determine trust level
        $level = $this->determineTrustLevel($score, $user);

        // Update reputation
        $reputation->update([
            'score' => $score,
            'level' => $level,
            'meta' => $this->calculateMeta($user),
        ]);

        return $reputation;
    }

    /**
     * Calculate reputation score based on user activity.
     */
    protected function calculateScore(User $user): int
    {
        $score = 0;

        // Get comment statistics
        $comments = Comment::where('user_id', $user->id)->get();

        // Approved comments: +5 points each
        $approvedCount = $comments->where('status', \App\Enums\CommentStatus::Approved)->count();
        $score += $approvedCount * 5;

        // Rejected comments: -10 points each
        $rejectedCount = $comments->where('status', \App\Enums\CommentStatus::Rejected)->count();
        $score -= $rejectedCount * 10;

        // Flagged comments: -5 points each
        $flaggedCount = $comments->where('status', \App\Enums\CommentStatus::Flagged)->count();
        $score -= $flaggedCount * 5;

        // Spam comments: -20 points each
        $spamCount = $comments->where('status', \App\Enums\CommentStatus::Spam)->count();
        $score -= $spamCount * 20;

        // Comment reactions (likes, helpful, etc.): +1 point each
        $reactionCount = DB::table('comment_reactions')
            ->whereIn('comment_id', $comments->pluck('id'))
            ->count();
        $score += $reactionCount;

        // Account age bonus (1 point per month, max 12)
        $accountAgeMonths = $user->created_at->diffInMonths(now());
        $score += min($accountAgeMonths, 12);

        // Ensure score is not negative
        return max(0, $score);
    }

    /**
     * Determine trust level based on score and user behavior.
     */
    protected function determineTrustLevel(int $score, User $user): string
    {
        // Check if user is banned
        if ($user->status === \App\Enums\UserStatus::Suspended) {
            return 'banned';
        }

        // Determine level based on score
        return match (true) {
            $score >= 200 => 'expert',
            $score >= 100 => 'trusted',
            $score >= 50 => 'member',
            $score >= 20 => 'contributor',
            default => 'new',
        };
    }

    /**
     * Calculate metadata for reputation.
     */
    protected function calculateMeta(User $user): array
    {
        $comments = Comment::where('user_id', $user->id)->get();

        return [
            'total_comments' => $comments->count(),
            'approved_comments' => $comments->where('status', \App\Enums\CommentStatus::Approved)->count(),
            'rejected_comments' => $comments->where('status', \App\Enums\CommentStatus::Rejected)->count(),
            'flagged_comments' => $comments->where('status', \App\Enums\CommentStatus::Flagged)->count(),
            'spam_comments' => $comments->where('status', \App\Enums\CommentStatus::Spam)->count(),
            'total_reactions' => DB::table('comment_reactions')
                ->whereIn('comment_id', $comments->pluck('id'))
                ->count(),
            'account_age_days' => $user->created_at->diffInDays(now()),
            'last_calculated' => now()->toIso8601String(),
        ];
    }

    /**
     * Get default metadata.
     */
    protected function getDefaultMeta(): array
    {
        return [
            'total_comments' => 0,
            'approved_comments' => 0,
            'rejected_comments' => 0,
            'flagged_comments' => 0,
            'spam_comments' => 0,
            'total_reactions' => 0,
            'account_age_days' => 0,
            'last_calculated' => now()->toIso8601String(),
        ];
    }

    /**
     * Update reputation after a moderation action.
     */
    public function updateAfterModeration(User $user, string $action, ?string $previousStatus = null): void
    {
        $reputation = UserReputation::firstOrCreate(
            ['user_id' => $user->id],
            [
                'score' => 0,
                'level' => 'new',
                'meta' => $this->getDefaultMeta(),
            ]
        );

        $scoreChange = $this->getScoreChangeForAction($action, $previousStatus);

        $newScore = max(0, $reputation->score + $scoreChange);
        $newLevel = $this->determineTrustLevel($newScore, $user);

        $reputation->update([
            'score' => $newScore,
            'level' => $newLevel,
        ]);

        Log::info('User reputation updated', [
            'user_id' => $user->id,
            'action' => $action,
            'score_change' => $scoreChange,
            'new_score' => $newScore,
            'new_level' => $newLevel,
        ]);
    }

    /**
     * Get score change for a moderation action.
     */
    protected function getScoreChangeForAction(string $action, ?string $previousStatus = null): int
    {
        return match ($action) {
            'approved' => 5,
            'rejected' => -10,
            'flagged' => -5,
            'spam' => -20,
            'deleted' => -20,
            'unflagged' => 5, // Restoring a flagged comment
            default => 0,
        };
    }

    /**
     * Check if user should be auto-approved based on reputation.
     */
    public function shouldAutoApprove(User $user): bool
    {
        $reputation = UserReputation::where('user_id', $user->id)->first();

        if (! $reputation) {
            return false;
        }

        // Auto-approve for trusted users
        return in_array($reputation->level, ['trusted', 'expert']) && $reputation->score >= 50;
    }

    /**
     * Check if user needs extra scrutiny based on reputation.
     */
    public function needsExtraScrutiny(User $user): bool
    {
        $reputation = UserReputation::where('user_id', $user->id)->first();

        if (! $reputation) {
            // New users need scrutiny
            return true;
        }

        // Users with low score or new level need scrutiny
        return $reputation->score < 10 || $reputation->level === 'new';
    }

    /**
     * Get reputation badge for display.
     */
    public function getBadge(User $user): array
    {
        $reputation = UserReputation::where('user_id', $user->id)->first();

        if (! $reputation) {
            return [
                'level' => 'new',
                'label' => 'New Member',
                'color' => 'gray',
                'icon' => '🌱',
            ];
        }

        return match ($reputation->level) {
            'expert' => [
                'level' => 'expert',
                'label' => 'Expert',
                'color' => 'purple',
                'icon' => '⭐',
            ],
            'trusted' => [
                'level' => 'trusted',
                'label' => 'Trusted',
                'color' => 'blue',
                'icon' => '✓',
            ],
            'member' => [
                'level' => 'member',
                'label' => 'Member',
                'color' => 'green',
                'icon' => '👤',
            ],
            'contributor' => [
                'level' => 'contributor',
                'label' => 'Contributor',
                'color' => 'teal',
                'icon' => '💬',
            ],
            'banned' => [
                'level' => 'banned',
                'label' => 'Banned',
                'color' => 'red',
                'icon' => '🚫',
            ],
            default => [
                'level' => 'new',
                'label' => 'New Member',
                'color' => 'gray',
                'icon' => '🌱',
            ],
        };
    }

    /**
     * Recalculate reputation for all users (batch operation).
     */
    public function recalculateAll(): int
    {
        $count = 0;

        User::chunk(100, function ($users) use (&$count) {
            foreach ($users as $user) {
                $this->calculateReputation($user);
                $count++;
            }
        });

        Log::info('Batch reputation recalculation completed', ['users_processed' => $count]);

        return $count;
    }

    /**
     * Get reputation statistics for analytics.
     */
    public function getStatistics(): array
    {
        return [
            'total_users' => User::count(),
            'by_level' => [
                'new' => UserReputation::where('level', 'new')->count(),
                'contributor' => UserReputation::where('level', 'contributor')->count(),
                'member' => UserReputation::where('level', 'member')->count(),
                'trusted' => UserReputation::where('level', 'trusted')->count(),
                'expert' => UserReputation::where('level', 'expert')->count(),
                'banned' => UserReputation::where('level', 'banned')->count(),
            ],
            'average_score' => UserReputation::avg('score'),
            'highest_score' => UserReputation::max('score'),
            'lowest_score' => UserReputation::min('score'),
        ];
    }
}
