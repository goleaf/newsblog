<?php

namespace App\Services;

use Illuminate\Support\LazyCollection;
use InvalidArgumentException;

class CsvParserService
{
    /**
     * Required CSV columns for validation.
     */
    protected array $requiredColumns = ['title', 'tags', 'categories'];

    /**
     * Detected CSV headers.
     */
    protected array $headers = [];

    /**
     * Parse CSV file lazily using LazyCollection for memory efficiency.
     */
    public function parseLazy(string $filePath): LazyCollection
    {
        if (! file_exists($filePath)) {
            throw new InvalidArgumentException("File not found: {$filePath}");
        }

        if (! is_readable($filePath)) {
            throw new InvalidArgumentException("File is not readable: {$filePath}");
        }

        $encoding = $this->detectEncoding($filePath);

        return LazyCollection::make(function () use ($filePath) {
            $file = fopen($filePath, 'r');

            if ($file === false) {
                throw new InvalidArgumentException("Unable to open file: {$filePath}");
            }

            try {
                // Read and parse headers from first line
                $headerLine = fgets($file);
                if ($headerLine === false) {
                    return;
                }

                $this->headers = $this->parseCsvLine($headerLine);
                $this->validateStructure($this->headers);

                // Yield each subsequent line as an associative array
                while (($line = fgets($file)) !== false) {
                    $line = trim($line);

                    // Skip empty lines
                    if (empty($line)) {
                        continue;
                    }

                    $values = $this->parseCsvLine($line);

                    // Ensure we have the same number of values as headers
                    if (count($values) === count($this->headers)) {
                        yield array_combine($this->headers, $values);
                    }
                }
            } finally {
                fclose($file);
            }
        });
    }

    /**
     * Detect file encoding (UTF-8 or ASCII).
     */
    public function detectEncoding(string $filePath): string
    {
        $file = fopen($filePath, 'r');

        if ($file === false) {
            throw new InvalidArgumentException("Unable to open file for encoding detection: {$filePath}");
        }

        // Read first 8KB to detect encoding
        $sample = fread($file, 8192);
        fclose($file);

        if ($sample === false) {
            return 'ASCII';
        }

        // Check for UTF-8 BOM
        if (substr($sample, 0, 3) === "\xEF\xBB\xBF") {
            return 'UTF-8';
        }

        // Check if content is valid UTF-8
        if (mb_check_encoding($sample, 'UTF-8')) {
            return 'UTF-8';
        }

        // Check for ASCII
        if (mb_check_encoding($sample, 'ASCII')) {
            return 'ASCII';
        }

        // Default to UTF-8
        return 'UTF-8';
    }

    /**
     * Validate that required columns exist in CSV headers.
     */
    public function validateStructure(array $headers): bool
    {
        $missingColumns = array_diff($this->requiredColumns, $headers);

        if (! empty($missingColumns)) {
            throw new InvalidArgumentException(
                'Missing required columns: '.implode(', ', $missingColumns)
            );
        }

        return true;
    }

    /**
     * Parse a single CSV line respecting quotes and delimiters.
     */
    protected function parseCsvLine(string $line): array
    {
        // Use str_getcsv for proper CSV parsing with quote handling
        $values = str_getcsv($line, ',', '"', '\\');

        // Trim whitespace from each value
        return array_map('trim', $values);
    }

    /**
     * Get the detected headers from the CSV file.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
