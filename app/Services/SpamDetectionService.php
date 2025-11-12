<?php

namespace App\Services;

class SpamDetectionService
{
    /**
     * Default blacklisted keywords for spam detection.
     */
    private array $blacklistedKeywords = [
        'viagra',
        'casino',
        'loan',
        'click here',
        'buy now',
        'limited offer',
        'act now',
        'free money',
        'weight loss',
        'make money fast',
    ];

    /**
     * Maximum allowed links in a comment.
     */
    private int $maxLinks = 3;

    /**
     * Minimum time (in seconds) required on page before submission.
     */
    private int $minSubmissionTime = 3;

    /**
     * Check if the comment content is spam.
     */
    public function isSpam(string $content, array $context = []): bool
    {
        // Check link count
        if ($this->hasExcessiveLinks($content)) {
            return true;
        }

        // Check submission speed
        if ($this->isSubmittedTooQuickly($context)) {
            return true;
        }

        // Check blacklisted keywords
        if ($this->containsBlacklistedWords($content)) {
            return true;
        }

        // Check honeypot field
        if ($this->honeypotFilled($context)) {
            return true;
        }

        return false;
    }

    /**
     * Check if content contains more than the allowed number of links.
     */
    public function hasExcessiveLinks(string $content): bool
    {
        $linkCount = substr_count(strtolower($content), 'http://') +
                     substr_count(strtolower($content), 'https://');

        return $linkCount > $this->maxLinks;
    }

    /**
     * Check if the submission was made too quickly after page load.
     */
    public function isSubmittedTooQuickly(array $context): bool
    {
        if (! isset($context['time_on_page'])) {
            return false;
        }

        return $context['time_on_page'] < $this->minSubmissionTime;
    }

    /**
     * Check if content contains blacklisted keywords.
     */
    public function containsBlacklistedWords(string $content): bool
    {
        $contentLower = strtolower($content);

        foreach ($this->blacklistedKeywords as $keyword) {
            if (str_contains($contentLower, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the honeypot field was filled (indicating bot activity).
     */
    public function honeypotFilled(array $context): bool
    {
        return ! empty($context['honeypot'] ?? null);
    }

    /**
     * Set custom blacklisted keywords.
     */
    public function setBlacklistedKeywords(array $keywords): self
    {
        $this->blacklistedKeywords = $keywords;

        return $this;
    }

    /**
     * Add keywords to the blacklist.
     */
    public function addBlacklistedKeywords(array $keywords): self
    {
        $this->blacklistedKeywords = array_merge($this->blacklistedKeywords, $keywords);

        return $this;
    }

    /**
     * Get the current blacklisted keywords.
     */
    public function getBlacklistedKeywords(): array
    {
        return $this->blacklistedKeywords;
    }

    /**
     * Set the maximum allowed links.
     */
    public function setMaxLinks(int $maxLinks): self
    {
        $this->maxLinks = $maxLinks;

        return $this;
    }

    /**
     * Set the minimum submission time.
     */
    public function setMinSubmissionTime(int $seconds): self
    {
        $this->minSubmissionTime = $seconds;

        return $this;
    }
}
