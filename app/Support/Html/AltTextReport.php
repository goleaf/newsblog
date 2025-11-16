<?php

namespace App\Support\Html;

class AltTextReport
{
    /**
     * @param int $totalImages
     * @param int $missingAltCount
     * @param array<int, array{src:string|null, alt:string|null, index:int}> $issues
     */
    public function __construct(
        public int $totalImages,
        public int $missingAltCount,
        public array $issues = [],
    ) {}

    public static function empty(): self
    {
        return new self(0, 0, []);
    }

    /**
     * @return array{
     *   total_images:int,
     *   missing_alt_count:int,
     *   issues: array<int, array{src:string|null, alt:string|null, index:int}>
     * }
     */
    public function toArray(): array
    {
        return [
            'total_images' => $this->totalImages,
            'missing_alt_count' => $this->missingAltCount,
            'issues' => $this->issues,
        ];
    }
}


