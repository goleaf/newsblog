<?php

namespace Tests\Unit;

use App\Enums\ArticleStatus;
use Tests\TestCase;

class ArticleStatusEnumTest extends TestCase
{
    public function test_article_status_enum_has_expected_values(): void
    {
        $this->assertSame('draft', ArticleStatus::Draft->value);
        $this->assertSame('published', ArticleStatus::Published->value);
        $this->assertSame('archived', ArticleStatus::Archived->value);
    }

    public function test_is_public(): void
    {
        $this->assertTrue(ArticleStatus::Published->isPublic());
        $this->assertFalse(ArticleStatus::Draft->isPublic());
        $this->assertFalse(ArticleStatus::Archived->isPublic());
    }

    public function test_is_editable(): void
    {
        $this->assertTrue(ArticleStatus::Draft->isEditable());
        $this->assertTrue(ArticleStatus::Scheduled->isEditable());
        $this->assertFalse(ArticleStatus::Published->isEditable());
        $this->assertFalse(ArticleStatus::Archived->isEditable());
    }
}
