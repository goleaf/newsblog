<?php

namespace Tests\Feature;

use App\Services\CsvParserService;
use InvalidArgumentException;
use Tests\TestCase;

class CsvParserServiceTest extends TestCase
{
    private CsvParserService $csvParserService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->csvParserService = new CsvParserService;
    }

    public function test_parses_valid_csv_file_lazily(): void
    {
        $filePath = database_path('data/test_small.csv');

        $collection = $this->csvParserService->parseLazy($filePath);

        $this->assertInstanceOf(\Illuminate\Support\LazyCollection::class, $collection);

        $rows = $collection->toArray();
        $this->assertCount(5, $rows);

        // Check first row
        $firstRow = $rows[0];
        $this->assertEquals('Getting Started with Laravel 12', $firstRow['title']);
        $this->assertEquals('laravel,php,web development', $firstRow['tags']);
        $this->assertEquals('Backend Development', $firstRow['categories']);
    }

    public function test_detects_utf8_encoding(): void
    {
        $filePath = database_path('data/test_encoding.csv');

        $encoding = $this->csvParserService->detectEncoding($filePath);

        $this->assertEquals('UTF-8', $encoding);
    }

    public function test_detects_ascii_encoding(): void
    {
        $filePath = database_path('data/test_small.csv');

        $encoding = $this->csvParserService->detectEncoding($filePath);

        $this->assertContains($encoding, ['UTF-8', 'ASCII']);
    }

    public function test_validates_structure_with_required_columns(): void
    {
        $headers = ['title', 'tags', 'categories'];

        $result = $this->csvParserService->validateStructure($headers);

        $this->assertTrue($result);
    }

    public function test_validates_structure_throws_exception_for_missing_columns(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required columns: categories');

        $headers = ['title', 'tags'];

        $this->csvParserService->validateStructure($headers);
    }

    public function test_throws_exception_for_nonexistent_file(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File not found');

        $this->csvParserService->parseLazy('nonexistent.csv');
    }

    public function test_throws_exception_for_malformed_csv_structure(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required columns: categories');

        $filePath = database_path('data/test_malformed.csv');

        // This should throw exception during header validation
        $collection = $this->csvParserService->parseLazy($filePath);
        $collection->toArray(); // Force evaluation
    }

    public function test_skips_empty_lines(): void
    {
        // Create a temporary CSV with empty lines
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_test_');
        file_put_contents($tempFile, "title,tags,categories\n\"Test Title\",\"tag1,tag2\",\"Category1\"\n\n\"Another Title\",\"tag3\",\"Category2\"\n");

        try {
            $collection = $this->csvParserService->parseLazy($tempFile);
            $rows = $collection->toArray();

            $this->assertCount(2, $rows);
            $this->assertEquals('Test Title', $rows[0]['title']);
            $this->assertEquals('Another Title', $rows[1]['title']);
        } finally {
            unlink($tempFile);
        }
    }

    public function test_handles_quoted_values_with_commas(): void
    {
        $filePath = database_path('data/test_small.csv');

        $collection = $this->csvParserService->parseLazy($filePath);
        $rows = $collection->toArray();

        // Check that comma-separated values within quotes are preserved
        $firstRow = $rows[0];
        $this->assertEquals('laravel,php,web development', $firstRow['tags']);
    }

    public function test_returns_headers_after_parsing(): void
    {
        $filePath = database_path('data/test_small.csv');

        $this->csvParserService->parseLazy($filePath)->toArray();

        $headers = $this->csvParserService->getHeaders();

        $this->assertEquals(['title', 'tags', 'categories'], $headers);
    }

    public function test_handles_large_files_with_lazy_collection(): void
    {
        // Create a temporary large CSV file
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_large_');
        $handle = fopen($tempFile, 'w');
        fputcsv($handle, ['title', 'tags', 'categories']);

        // Write 1000 rows
        for ($i = 1; $i <= 1000; $i++) {
            fputcsv($handle, ["Title {$i}", "tag{$i}", "Category{$i}"]);
        }
        fclose($handle);

        try {
            $collection = $this->csvParserService->parseLazy($tempFile);

            // Verify it's lazy by only taking first 10
            $firstTen = $collection->take(10)->toArray();
            $this->assertCount(10, $firstTen);
            $this->assertEquals('Title 1', $firstTen[0]['title']);

            // Verify total count
            $collection = $this->csvParserService->parseLazy($tempFile);
            $this->assertCount(1000, $collection->toArray());
        } finally {
            unlink($tempFile);
        }
    }
}
