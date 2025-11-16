<?php

namespace Tests\Unit;

use App\Enums\NotificationType;
use Tests\TestCase;

class NotificationTypeEnumTest extends TestCase
{
    public function test_notification_type_enum_has_expected_values(): void
    {
        $this->assertSame('comment_reply', NotificationType::CommentReply->value);
        $this->assertSame('new_follower', NotificationType::NewFollower->value);
        $this->assertSame('author_new_article', NotificationType::AuthorNewArticle->value);
        $this->assertSame('comment_reaction', NotificationType::CommentReaction->value);
        $this->assertSame('mention', NotificationType::Mention->value);
    }

    public function test_default_email_enabled(): void
    {
        $this->assertTrue(NotificationType::CommentReply->defaultEmailEnabled());
        $this->assertTrue(NotificationType::NewFollower->defaultEmailEnabled());
        $this->assertTrue(NotificationType::AuthorNewArticle->defaultEmailEnabled());
        $this->assertFalse(NotificationType::CommentReaction->defaultEmailEnabled());
        $this->assertFalse(NotificationType::Mention->defaultEmailEnabled());
    }

    public function test_default_in_app_enabled(): void
    {
        $this->assertTrue(NotificationType::CommentReply->defaultInAppEnabled());
        $this->assertTrue(NotificationType::NewFollower->defaultInAppEnabled());
        $this->assertTrue(NotificationType::CommentReaction->defaultInAppEnabled());
    }
}
