<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use Tests\TestCase;

class CategoryDescendantsTest extends TestCase
{
    public function test_meta_tags_and_description_defaults(): void
    {
        $category = new Category([
            'name' => 'Tech',
            'slug' => 'tech',
            // No description provided to exercise fallback
        ]);

        $meta = $category->getMetaTags();
        $this->assertSame('website', $meta['og:type']);
        $this->assertSame('summary', $meta['twitter:card']);
        $this->assertStringContainsString('Tech', $meta['title']);

        $desc = $category->getMetaDescription();
        $this->assertStringContainsString('Tech', $desc);
        $this->assertLessThanOrEqual(160, strlen($desc));
    }
}
