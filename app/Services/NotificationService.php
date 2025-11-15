<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    /**
     * Create a new notification for a user.
     */
    public function create(
        User $user,
        string $type,
        string $title,
        string $message,
        ?string $actionUrl = null,
        ?string $icon = null,
        ?array $data = null
    ): Notification {
        return Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'icon' => $icon,
            'data' => $data,
        ]);
    }

    /**
     * Create a comment reply notification.
     */
    public function notifyCommentReply(User $user, $comment, $reply): Notification
    {
        return $this->create(
            user: $user,
            type: Notification::TYPE_COMMENT_REPLY,
            title: 'New reply to your comment',
            message: "{$reply->user->name} replied to your comment",
            actionUrl: route('post.show', $comment->post->slug).'#comment-'.$reply->id,
            icon: 'chat-bubble-left-right',
            data: [
                'comment_id' => $comment->id,
                'reply_id' => $reply->id,
                'post_id' => $comment->post_id,
            ]
        );
    }

    /**
     * Create a post published notification.
     */
    public function notifyPostPublished(User $user, $post): Notification
    {
        return $this->create(
            user: $user,
            type: Notification::TYPE_POST_PUBLISHED,
            title: 'New article published',
            message: "New article: {$post->title}",
            actionUrl: route('post.show', $post->slug),
            icon: 'newspaper',
            data: [
                'post_id' => $post->id,
            ]
        );
    }

    /**
     * Create a comment approved notification.
     */
    public function notifyCommentApproved(User $user, $comment): Notification
    {
        return $this->create(
            user: $user,
            type: Notification::TYPE_COMMENT_APPROVED,
            title: 'Your comment was approved',
            message: 'Your comment on "'.$comment->post->title.'" has been approved',
            actionUrl: route('post.show', $comment->post->slug).'#comment-'.$comment->id,
            icon: 'check-circle',
            data: [
                'comment_id' => $comment->id,
                'post_id' => $comment->post_id,
            ]
        );
    }

    /**
     * Create a series updated notification.
     */
    public function notifySeriesUpdated(User $user, $series, $newPost): Notification
    {
        return $this->create(
            user: $user,
            type: Notification::TYPE_SERIES_UPDATED,
            title: 'Series updated',
            message: "New article added to \"{$series->name}\"",
            actionUrl: route('post.show', $newPost->slug),
            icon: 'book-open',
            data: [
                'series_id' => $series->id,
                'post_id' => $newPost->id,
            ]
        );
    }

    /**
     * Get unread notifications for a user.
     */
    public function getUnread(User $user, int $limit = 10)
    {
        return $user->notifications()
            ->unread()
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get all notifications for a user.
     */
    public function getAll(User $user, int $perPage = 20)
    {
        return $user->notifications()
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Notification $notification): void
    {
        $notification->markAsRead();
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(User $user): void
    {
        $user->notifications()
            ->unread()
            ->update(['read_at' => now()]);
    }

    /**
     * Get unread count for a user.
     */
    public function getUnreadCount(User $user): int
    {
        return $user->notifications()
            ->unread()
            ->count();
    }

    /**
     * Delete old read notifications.
     */
    public function deleteOldNotifications(int $daysOld = 30): int
    {
        return Notification::read()
            ->where('read_at', '<', now()->subDays($daysOld))
            ->delete();
    }
}
