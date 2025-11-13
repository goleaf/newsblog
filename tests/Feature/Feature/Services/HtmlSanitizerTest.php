<?php

namespace Tests\Feature\Feature\Services;

use App\Services\HtmlSanitizer;
use Tests\TestCase;

class HtmlSanitizerTest extends TestCase
{
    protected HtmlSanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = app(HtmlSanitizer::class);
    }

    public function test_sanitizes_dangerous_html(): void
    {
        $dangerous = '<script>alert("XSS")</script><p>Safe content</p>';
        $sanitized = $this->sanitizer->sanitize($dangerous);

        $this->assertStringNotContainsString('<script>', $sanitized);
        $this->assertStringContainsString('<p>Safe content</p>', $sanitized);
    }

    public function test_allows_safe_html_tags(): void
    {
        $safe = '<p>Paragraph</p><strong>Bold</strong><em>Italic</em><a href="https://example.com">Link</a>';
        $sanitized = $this->sanitizer->sanitize($safe);

        $this->assertStringContainsString('<p>Paragraph</p>', $sanitized);
        $this->assertStringContainsString('<strong>Bold</strong>', $sanitized);
        $this->assertStringContainsString('<em>Italic</em>', $sanitized);
        $this->assertStringContainsString('<a href="https://example.com">Link</a>', $sanitized);
    }

    public function test_removes_dangerous_attributes(): void
    {
        $dangerous = '<a href="javascript:alert(1)">Click</a><img src="x" onerror="alert(1)">';
        $sanitized = $this->sanitizer->sanitize($dangerous);

        $this->assertStringNotContainsString('javascript:', $sanitized);
        $this->assertStringNotContainsString('onerror', $sanitized);
    }

    public function test_handles_null_input(): void
    {
        $result = $this->sanitizer->sanitize(null);
        $this->assertNull($result);
    }

    public function test_handles_empty_string(): void
    {
        $result = $this->sanitizer->sanitize('');
        $this->assertEquals('', $result);
    }

    public function test_sanitizes_multiple_fields(): void
    {
        $fields = [
            'title' => '<script>alert(1)</script>Title',
            'content' => '<p>Content</p><script>bad</script>',
            'safe' => '<strong>Bold</strong>',
        ];

        $sanitized = $this->sanitizer->sanitizeMultiple($fields);

        $this->assertStringNotContainsString('<script>', $sanitized['title']);
        $this->assertStringNotContainsString('<script>', $sanitized['content']);
        $this->assertStringContainsString('<strong>Bold</strong>', $sanitized['safe']);
    }

    public function test_preserves_allowed_classes(): void
    {
        $html = '<span class="highlight">Text</span><div class="container">Content</div>';
        $sanitized = $this->sanitizer->sanitize($html);

        $this->assertStringContainsString('class="highlight"', $sanitized);
        $this->assertStringContainsString('class="container"', $sanitized);
    }

    public function test_allows_images_with_safe_attributes(): void
    {
        $html = '<img src="https://example.com/image.jpg" alt="Description" width="100" height="100">';
        $sanitized = $this->sanitizer->sanitize($html);

        $this->assertStringContainsString('<img', $sanitized);
        $this->assertStringContainsString('src="https://example.com/image.jpg"', $sanitized);
        $this->assertStringContainsString('alt="Description"', $sanitized);
    }
}
