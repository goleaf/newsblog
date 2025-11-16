<?php

namespace Tests\Unit;

use App\Enums\CommentStatus;
use Tests\TestCase;

class CommentStatusEnumTest extends TestCase
{
    public function test_comment_status_enum_has_expected_values(): void
    {
        $this->assertSame('pending', CommentStatus::Pending->value);
        $this->assertSame('approved', CommentStatus::Approved->value);
        $this->assertSame('rejected', CommentStatus::Rejected->value);
        $this->assertSame('flagged', CommentStatus::Flagged->value);
        $this->assertSame('spam', CommentStatus::Spam->value);
    }

    public function test_comment_status_labels(): void
    {
        $this->assertSame('Pending', CommentStatus::Pending->label());
        $this->assertSame('Approved', CommentStatus::Approved->label());
        $this->assertSame('Rejected', CommentStatus::Rejected->label());
        $this->assertSame('Flagged', CommentStatus::Flagged->label());
        $this->assertSame('Spam', CommentStatus::Spam->label());
    }
}
