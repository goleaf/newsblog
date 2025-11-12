<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

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

    protected FuzzySearchService $fuzzySearchService;

    /**
     * Fuzzy matching threshold for spam detection (0-100).
     */
    protected int $fuzzyThreshold = 70;

    public function __construct(FuzzySearchService $fuzzySearchService)
    {
        $this->fuzzySearchService = $fuzzySearchService;
    }

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
     * Check if content contains blacklisted keywords using fuzzy matching.
     */
    public function containsBlacklistedWords(string $content): bool
    {
        $contentLower = mb_strtolower($content);
        $matchedKeywords = [];
        $maxScore = 0;
        $bestMatch = null;

        foreach ($this->blacklistedKeywords as $keyword) {
            // First check for exact match (faster)
            if (str_contains($contentLower, $keyword)) {
                $matchedKeywords[] = [
                    'keyword' => $keyword,
                    'score' => 100,
                    'match_type' => 'exact',
                ];
                $maxScore = 100;
                $bestMatch = $keyword;

                Log::info('Spam detection: Exact keyword match found', [
                    'keyword' => $keyword,
                    'score' => 100,
                    'match_type' => 'exact',
                ]);

                continue;
            }

            // Use fuzzy matching for variations
            $score = $this->fuzzySearchService->calculateFuzzyScore($keyword, $contentLower);

            if ($score >= $this->fuzzyThreshold) {
                $matchedKeywords[] = [
                    'keyword' => $keyword,
                    'score' => $score,
                    'match_type' => 'fuzzy',
                ];

                if ($score > $maxScore) {
                    $maxScore = $score;
                    $bestMatch = $keyword;
                }
            }
        }

        // Log matched keywords and scores
        if (! empty($matchedKeywords)) {
            Log::warning('Spam detection: Blacklisted keywords matched', [
                'matched_keywords' => $matchedKeywords,
                'max_score' => $maxScore,
                'best_match' => $bestMatch,
                'content_preview' => mb_substr($content, 0, 100),
            ]);
        }

        return ! empty($matchedKeywords);
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
     * Set the fuzzy matching threshold for spam detection.
     */
    public function setFuzzyThreshold(int $threshold): self
    {
        $this->fuzzyThreshold = max(0, min(100, $threshold));

        return $this;
    }

    /**
     * Get the current fuzzy matching threshold.
     */
    public function getFuzzyThreshold(): int
    {
        return $this->fuzzyThreshold;
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
