<?php

namespace Tests\Unit;

use App\Enums\ModerationReason;
use Tests\TestCase;

class ModerationReasonEnumTest extends TestCase
{
    public function test_moderation_reason_enum_has_expected_values(): void
    {
        $this->assertSame('spam', ModerationReason::Spam->value);
        $this->assertSame('offensive', ModerationReason::Offensive->value);
        $this->assertSame('off_topic', ModerationReason::OffTopic->value);
        $this->assertSame('prohibited_content', ModerationReason::ProhibitedContent->value);
        $this->assertSame('harassment', ModerationReason::Harassment->value);
    }

    public function test_should_auto_reject(): void
    {
        $this->assertTrue(ModerationReason::Spam->shouldAutoReject());
        $this->assertTrue(ModerationReason::ProhibitedContent->shouldAutoReject());
        $this->assertTrue(ModerationReason::Harassment->shouldAutoReject());
        $this->assertFalse(ModerationReason::OffTopic->shouldAutoReject());
        $this->assertFalse(ModerationReason::LowQuality->shouldAutoReject());
    }

    public function test_severity_levels(): void
    {
        $this->assertSame('high', ModerationReason::Spam->severity());
        $this->assertSame('high', ModerationReason::Offensive->severity());
        $this->assertSame('high', ModerationReason::Harassment->severity());
        $this->assertSame('medium', ModerationReason::OffTopic->severity());
        $this->assertSame('medium', ModerationReason::LowQuality->severity());
        $this->assertSame('low', ModerationReason::Other->severity());
    }
}
