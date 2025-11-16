<?php

namespace App\Services;

use App\Support\Html\AltTextReport;

class AltTextValidator
{
    /**
     * Scan HTML content and detect images with missing or empty alt attributes.
     */
    public function scanHtml(string $html): AltTextReport
    {
        if (trim($html) === '') {
            return AltTextReport::empty();
        }

        // Suppress warnings for malformed HTML while parsing
        $dom = new \DOMDocument();
        $internalErrors = libxml_use_internal_errors(true);

        // Ensure proper encoding handling
        $wrappedHtml = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head><body>'.$html.'</body></html>';
        $dom->loadHTML($wrappedHtml);

        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        $images = $dom->getElementsByTagName('img');
        $total = 0;
        $missing = 0;
        $issues = [];

        $index = 0;
        foreach ($images as $img) {
            $total++;
            $alt = $img->getAttribute('alt');
            $hasAlt = $img->hasAttribute('alt') && trim($alt) !== '';
            if (! $hasAlt) {
                $missing++;
                $issues[] = [
                    'src' => $img->getAttribute('src') ?: null,
                    'alt' => $img->hasAttribute('alt') ? $alt : null,
                    'index' => $index,
                ];
            }
            $index++;
        }

        return new AltTextReport($total, $missing, $issues);
    }
}
