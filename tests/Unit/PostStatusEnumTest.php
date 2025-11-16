<?php

namespace Tests\Unit;

use App\Enums\PostStatus;
use Tests\TestCase;

class PostStatusEnumTest extends TestCase
{
    public function test_post_status_enum_has_expected_values(): void
    {
        $this->assertSame('draft', PostStatus::Draft->value);
        $this->assertSame('scheduled', PostStatus::Scheduled->value);
        $this->assertSame('published', PostStatus::Published->value);
        $this->assertSame('archived', PostStatus::Archived->value);
    }
}

