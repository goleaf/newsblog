<?php

namespace Tests\Feature;

use App\Services\SpamDetectionService;
use Tests\TestCase;

class SpamDetectionServiceTest extends TestCase
{
    private SpamDetectionService $spamDetectionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->spamDetectionService = new SpamDetectionService;
    }

    public function test_detects_excessive_links(): void
    {
        $content = 'Check out http://example.com and http://test.com and http://spam.com and http://more.com';

        $this->assertTrue($this->spamDetectionService->hasExcessiveLinks($content));
    }

    public function test_allows_content_with_acceptable_links(): void
    {
        $content = 'Check out http://example.com and http://test.com';

        $this->assertFalse($this->spamDetectionService->hasExcessiveLinks($content));
    }

    public function test_detects_quick_submission(): void
    {
        $context = ['time_on_page' => 2];

        $this->assertTrue($this->spamDetectionService->isSubmittedTooQuickly($context));
    }

    public function test_allows_normal_submission_speed(): void
    {
        $context = ['time_on_page' => 5];

        $this->assertFalse($this->spamDetectionService->isSubmittedTooQuickly($context));
    }

    public function test_detects_blacklisted_keywords(): void
    {
        $content = 'Buy viagra now!';

        $this->assertTrue($this->spamDetectionService->containsBlacklistedWords($content));
    }

    public function test_allows_clean_content(): void
    {
        $content = 'This is a legitimate comment about the article.';

        $this->assertFalse($this->spamDetectionService->containsBlacklistedWords($content));
    }

    public function test_detects_honeypot_field(): void
    {
        $context = ['honeypot' => 'bot filled this'];

        $this->assertTrue($this->spamDetectionService->honeypotFilled($context));
    }

    public function test_allows_empty_honeypot(): void
    {
        $context = ['honeypot' => ''];

        $this->assertFalse($this->spamDetectionService->honeypotFilled($context));
    }

    public function test_is_spam_returns_true_for_spam_content(): void
    {
        $content = 'Buy viagra now! http://spam1.com http://spam2.com http://spam3.com http://spam4.com';
        $context = ['time_on_page' => 1, 'honeypot' => ''];

        $this->assertTrue($this->spamDetectionService->isSpam($content, $context));
    }

    public function test_is_spam_returns_false_for_legitimate_content(): void
    {
        $content = 'This is a great article! Thanks for sharing.';
        $context = ['time_on_page' => 10, 'honeypot' => ''];

        $this->assertFalse($this->spamDetectionService->isSpam($content, $context));
    }

    public function test_can_set_custom_blacklisted_keywords(): void
    {
        $this->spamDetectionService->setBlacklistedKeywords(['badword', 'spam']);

        $this->assertTrue($this->spamDetectionService->containsBlacklistedWords('This contains badword'));
        $this->assertFalse($this->spamDetectionService->containsBlacklistedWords('This contains viagra'));
    }

    public function test_can_add_blacklisted_keywords(): void
    {
        $this->spamDetectionService->addBlacklistedKeywords(['newbadword']);

        $this->assertTrue($this->spamDetectionService->containsBlacklistedWords('This contains newbadword'));
        $this->assertTrue($this->spamDetectionService->containsBlacklistedWords('This contains viagra'));
    }

    public function test_can_set_max_links(): void
    {
        $this->spamDetectionService->setMaxLinks(5);

        $content = 'http://1.com http://2.com http://3.com http://4.com http://5.com';
        $this->assertFalse($this->spamDetectionService->hasExcessiveLinks($content));

        $content .= ' http://6.com';
        $this->assertTrue($this->spamDetectionService->hasExcessiveLinks($content));
    }

    public function test_can_set_min_submission_time(): void
    {
        $this->spamDetectionService->setMinSubmissionTime(5);

        $this->assertTrue($this->spamDetectionService->isSubmittedTooQuickly(['time_on_page' => 4]));
        $this->assertFalse($this->spamDetectionService->isSubmittedTooQuickly(['time_on_page' => 6]));
    }
}
