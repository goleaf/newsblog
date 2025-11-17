<?php

namespace App\Services;

use App\Models\Notification;
use Illuminate\Support\Collection;

class NotificationGroupingService
{
    /**
     * Group similar notifications together.
     */
    public function groupNotifications(Collection $notifications): Collection
    {
        $grouped = collect();
        $processedIds = [];

        foreach ($notifications as $notification) {
            // Skip if already processed
            if (in_array($notification->id, $processedIds)) {
                continue;
            }

            // Find similar notifications
            $similar = $this->findSimilarNotifications($notification, $notifications, $processedIds);

            if ($similar->count() > 1) {
                // Create grouped notification
                $grouped->push([
                    'id' => 'group_'.$notification->id,
                    'type' => 'grouped',
                    'notification_type' => $notification->type,
                    'count' => $similar->count(),
                    'notifications' => $similar,
                    'title' => $this->getGroupedTitle($notification->type, $similar->count()),
                    'message' => $this->getGroupedMessage($notification->type, $similar),
                    'action_url' => $notification->action_url,
                    'icon' => $notification->icon,
                    'created_at' => $similar->first()->created_at,
                    'read_at' => $similar->every(fn ($n) => $n->read_at) ? $similar->first()->read_at : null,
                    'is_grouped' => true,
                ]);

                // Mark all as processed
                $processedIds = array_merge($processedIds, $similar->pluck('id')->toArray());
            } else {
                // Add individual notification
                $grouped->push([
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'action_url' => $notification->action_url,
                    'icon' => $notification->icon,
                    'data' => $notification->data,
                    'created_at' => $notification->created_at,
                    'read_at' => $notification->read_at,
                    'is_grouped' => false,
                ]);

                $processedIds[] = $notification->id;
            }
        }

        return $grouped;
    }

    /**
     * Find similar notifications that can be grouped.
     */
    protected function findSimilarNotifications(
        Notification $notification,
        Collection $notifications,
        array $processedIds
    ): Collection {
        $timeWindow = now()->subHours(24); // Group notifications within 24 hours

        return $notifications->filter(function ($n) use ($notification, $processedIds, $timeWindow) {
            // Skip if already processed
            if (in_array($n->id, $processedIds)) {
                return false;
            }

            // Must be same type
            if ($n->type !== $notification->type) {
                return false;
            }

            // Must be within time window
            if ($n->created_at->lt($timeWindow)) {
                return false;
            }

            // Type-specific grouping logic
            return $this->canGroupByType($notification, $n);
        });
    }

    /**
     * Determine if two notifications can be grouped based on their type.
     */
    protected function canGroupByType(Notification $notification1, Notification $notification2): bool
    {
        $data1 = $notification1->data ?? [];
        $data2 = $notification2->data ?? [];

        return match ($notification1->type) {
            // Group comment replies on the same post
            Notification::TYPE_COMMENT_REPLY => ($data1['post_id'] ?? null) === ($data2['post_id'] ?? null),

            // Group new followers
            Notification::TYPE_NEW_FOLLOWER => true,

            // Group new articles from the same author
            'author_new_article' => ($data1['author_id'] ?? null) === ($data2['author_id'] ?? null),

            // Group reactions on the same comment
            'comment_reaction' => ($data1['comment_id'] ?? null) === ($data2['comment_id'] ?? null),

            // Group mentions in the same context
            'mention' => ($data1['mentionable_id'] ?? null) === ($data2['mentionable_id'] ?? null)
                && ($data1['mentionable_type'] ?? null) === ($data2['mentionable_type'] ?? null),

            default => false,
        };
    }

    /**
     * Get the grouped notification title.
     */
    protected function getGroupedTitle(string $type, int $count): string
    {
        return match ($type) {
            Notification::TYPE_COMMENT_REPLY => "{$count} new replies to your comment",
            Notification::TYPE_NEW_FOLLOWER => "{$count} new followers",
            'author_new_article' => "{$count} new articles",
            'comment_reaction' => "{$count} reactions to your comment",
            'mention' => "{$count} new mentions",
            default => "{$count} new notifications",
        };
    }

    /**
     * Get the grouped notification message.
     */
    protected function getGroupedMessage(string $type, Collection $notifications): string
    {
        $names = $notifications->take(3)->map(function ($notification) use ($type) {
            $data = $notification->data ?? [];

            return match ($type) {
                Notification::TYPE_COMMENT_REPLY => $data['author_name'] ?? 'Someone',
                Notification::TYPE_NEW_FOLLOWER => $data['follower_name'] ?? 'Someone',
                'author_new_article' => $data['author_name'] ?? 'Someone',
                'comment_reaction' => $data['reactor_name'] ?? 'Someone',
                'mention' => $data['mentioner_name'] ?? 'Someone',
                default => 'Someone',
            };
        });

        $count = $notifications->count();
        $remaining = $count - 3;

        if ($count <= 3) {
            return $names->join(', ', ' and ');
        }

        return $names->join(', ')." and {$remaining} others";
    }

    /**
     * Expand a grouped notification to show individual notifications.
     */
    public function expandGroup(string $groupId, Collection $notifications): Collection
    {
        // Extract the original notification ID from the group ID
        $originalId = (int) str_replace('group_', '', $groupId);

        // Find the original notification
        $original = $notifications->firstWhere('id', $originalId);

        if (! $original) {
            return collect();
        }

        // Find all similar notifications
        return $this->findSimilarNotifications($original, $notifications, []);
    }
}
