<?php

namespace App\Enums;

enum NotificationType: string
{
    case CommentReply = 'comment_reply';
    case NewFollower = 'new_follower';
    case AuthorNewArticle = 'author_new_article';
    case CommentReaction = 'comment_reaction';
    case Mention = 'mention';
    case ArticlePublished = 'article_published';
    case CommentApproved = 'comment_approved';
    case CommentRejected = 'comment_rejected';
    case ModerationAction = 'moderation_action';

    /**
     * Get all notification type values as an array.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            'Comment Reply' => self::CommentReply->value,
            'New Follower' => self::NewFollower->value,
            'Author New Article' => self::AuthorNewArticle->value,
            'Comment Reaction' => self::CommentReaction->value,
            'Mention' => self::Mention->value,
            'Article Published' => self::ArticlePublished->value,
            'Comment Approved' => self::CommentApproved->value,
            'Comment Rejected' => self::CommentRejected->value,
            'Moderation Action' => self::ModerationAction->value,
        ];
    }

    /**
     * Get the display label for the notification type.
     */
    public function label(): string
    {
        return match ($this) {
            self::CommentReply => 'Comment Reply',
            self::NewFollower => 'New Follower',
            self::AuthorNewArticle => 'Author New Article',
            self::CommentReaction => 'Comment Reaction',
            self::Mention => 'Mention',
            self::ArticlePublished => 'Article Published',
            self::CommentApproved => 'Comment Approved',
            self::CommentRejected => 'Comment Rejected',
            self::ModerationAction => 'Moderation Action',
        };
    }

    /**
     * Check if this notification type should be sent via email by default.
     */
    public function defaultEmailEnabled(): bool
    {
        return match ($this) {
            self::CommentReply,
            self::NewFollower,
            self::AuthorNewArticle,
            self::ArticlePublished => true,
            default => false,
        };
    }

    /**
     * Check if this notification type should be sent via in-app notification by default.
     */
    public function defaultInAppEnabled(): bool
    {
        return true;
    }
}
