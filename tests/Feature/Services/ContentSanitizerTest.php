<?php

namespace Tests\Feature\Services;

use App\Services\ContentSanitizer;
use Tests\TestCase;

class ContentSanitizerTest extends TestCase
{
    protected ContentSanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = app(ContentSanitizer::class);
    }

    /** @test */
    public function it_sanitizes_article_content_and_allows_safe_html(): void
    {
        $html = '<p>This is a <strong>test</strong> article with <a href="https://example.com">a link</a>.</p>';
        $result = $this->sanitizer->sanitizeArticle($html);

        $this->assertStringContainsString('<p>', $result);
        $this->assertStringContainsString('<strong>test</strong>', $result);
        $this->assertStringContainsString('href="https://example.com"', $result);
        $this->assertStringContainsString('a link', $result);
    }

    /** @test */
    public function it_removes_dangerous_javascript_from_article_content(): void
    {
        $html = '<p>Test</p><script>alert("XSS")</script>';
        $result = $this->sanitizer->sanitizeArticle($html);

        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('alert', $result);
        $this->assertStringContainsString('<p>Test</p>', $result);
    }

    /** @test */
    public function it_removes_javascript_protocol_from_article_links(): void
    {
        $html = '<a href="javascript:alert(\'XSS\')">Click me</a>';
        $result = $this->sanitizer->sanitizeArticle($html);

        $this->assertStringNotContainsString('javascript:', $result);
    }

    /** @test */
    public function it_removes_onclick_attributes_from_article_content(): void
    {
        $html = '<p onclick="alert(\'XSS\')">Click me</p>';
        $result = $this->sanitizer->sanitizeArticle($html);

        $this->assertStringNotContainsString('onclick', $result);
    }

    /** @test */
    public function it_sanitizes_comment_content_and_allows_limited_html(): void
    {
        $html = '<p>This is a <strong>comment</strong> with <a href="https://example.com">a link</a>.</p>';
        $result = $this->sanitizer->sanitizeComment($html);

        $this->assertStringContainsString('<p>', $result);
        $this->assertStringContainsString('<strong>comment</strong>', $result);
        $this->assertStringContainsString('href="https://example.com"', $result);
        $this->assertStringContainsString('a link', $result);
    }

    /** @test */
    public function it_removes_dangerous_javascript_from_comment_content(): void
    {
        $html = '<p>Test comment</p><script>alert("XSS")</script>';
        $result = $this->sanitizer->sanitizeComment($html);

        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('alert', $result);
        $this->assertStringContainsString('Test comment', $result);
    }

    /** @test */
    public function it_removes_disallowed_tags_from_comments(): void
    {
        $html = '<p>Comment</p><iframe src="evil.com"></iframe>';
        $result = $this->sanitizer->sanitizeComment($html);

        $this->assertStringNotContainsString('<iframe>', $result);
        $this->assertStringContainsString('Comment', $result);
    }

    /** @test */
    public function it_sanitizes_bio_content_and_allows_minimal_html(): void
    {
        $html = 'I am a <strong>developer</strong> at <a href="https://example.com">Example Corp</a>.';
        $result = $this->sanitizer->sanitizeBio($html);

        $this->assertStringContainsString('<strong>developer</strong>', $result);
        $this->assertStringContainsString('href="https://example.com"', $result);
        $this->assertStringContainsString('Example Corp', $result);
    }

    /** @test */
    public function it_removes_dangerous_javascript_from_bio_content(): void
    {
        $html = 'Developer<script>alert("XSS")</script>';
        $result = $this->sanitizer->sanitizeBio($html);

        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('alert', $result);
        $this->assertStringContainsString('Developer', $result);
    }

    /** @test */
    public function it_removes_disallowed_tags_from_bio(): void
    {
        $html = 'Developer<div>Extra content</div>';
        $result = $this->sanitizer->sanitizeBio($html);

        $this->assertStringNotContainsString('<div>', $result);
        $this->assertStringContainsString('Developer', $result);
        $this->assertStringContainsString('Extra content', $result);
    }

    /** @test */
    public function it_handles_null_input_gracefully(): void
    {
        $this->assertNull($this->sanitizer->sanitizeArticle(null));
        $this->assertNull($this->sanitizer->sanitizeComment(null));
        $this->assertNull($this->sanitizer->sanitizeBio(null));
    }

    /** @test */
    public function it_handles_empty_string_input_gracefully(): void
    {
        $this->assertSame('', $this->sanitizer->sanitizeArticle(''));
        $this->assertSame('', $this->sanitizer->sanitizeComment(''));
        $this->assertSame('', $this->sanitizer->sanitizeBio(''));
    }

    /** @test */
    public function it_sanitizes_multiple_fields_at_once(): void
    {
        $fields = [
            'field1' => '<p>Test 1</p><script>alert("XSS")</script>',
            'field2' => '<p>Test 2</p>',
            'field3' => null,
        ];

        $result = $this->sanitizer->sanitizeMultiple($fields, 'comment');

        $this->assertStringNotContainsString('<script>', $result['field1']);
        $this->assertStringContainsString('Test 1', $result['field1']);
        $this->assertStringContainsString('<p>Test 2</p>', $result['field2']);
        $this->assertNull($result['field3']);
    }

    /** @test */
    public function it_adds_target_blank_to_external_links_in_articles(): void
    {
        $html = '<a href="https://example.com">External Link</a>';
        $result = $this->sanitizer->sanitizeArticle($html);

        $this->assertStringContainsString('target="_blank"', $result);
    }

    /** @test */
    public function it_removes_iframe_tags_from_all_content_types(): void
    {
        $iframe = '<iframe src="https://evil.com"></iframe>';

        $this->assertStringNotContainsString('<iframe>', $this->sanitizer->sanitizeArticle($iframe));
        $this->assertStringNotContainsString('<iframe>', $this->sanitizer->sanitizeComment($iframe));
        $this->assertStringNotContainsString('<iframe>', $this->sanitizer->sanitizeBio($iframe));
    }

    /** @test */
    public function it_removes_style_attributes_from_content(): void
    {
        $html = '<p style="color: red;">Styled text</p>';
        $result = $this->sanitizer->sanitizeComment($html);

        $this->assertStringNotContainsString('style=', $result);
        $this->assertStringContainsString('Styled text', $result);
    }

    /** @test */
    public function it_preserves_code_blocks_in_articles(): void
    {
        $html = '<pre><code>function test() { return true; }</code></pre>';
        $result = $this->sanitizer->sanitizeArticle($html);

        $this->assertStringContainsString('<pre>', $result);
        $this->assertStringContainsString('<code>', $result);
        $this->assertStringContainsString('function test()', $result);
    }

    /** @test */
    public function it_preserves_lists_in_comments(): void
    {
        $html = '<ul><li>Item 1</li><li>Item 2</li></ul>';
        $result = $this->sanitizer->sanitizeComment($html);

        $this->assertStringContainsString('<ul>', $result);
        $this->assertStringContainsString('<li>Item 1</li>', $result);
        $this->assertStringContainsString('<li>Item 2</li>', $result);
    }
}
