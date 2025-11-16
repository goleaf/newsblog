<?php

namespace Tests\Unit;

use App\Services\AltTextValidator;
use PHPUnit\Framework\TestCase;

class AltTextValidatorTest extends TestCase
{
    protected AltTextValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new AltTextValidator();
    }

    public function test_it_returns_empty_report_for_empty_html(): void
    {
        $report = $this->validator->scanHtml('');
        $this->assertSame(0, $report->totalImages);
        $this->assertSame(0, $report->missingAltCount);
        $this->assertSame([], $report->issues);
    }

    public function test_it_counts_images_and_missing_alt(): void
    {
        $html = '<p>Test</p><img src="/a.jpg" alt="A"><img src="/b.jpg"><img src="/c.jpg" alt="">';
        $report = $this->validator->scanHtml($html);

        $this->assertSame(3, $report->totalImages);
        $this->assertSame(2, $report->missingAltCount);
        $this->assertCount(2, $report->issues);
        $this->assertSame('/b.jpg', $report->issues[0]['src']);
        $this->assertNull($report->issues[0]['alt']);
        $this->assertSame('/c.jpg', $report->issues[1]['src']);
        $this->assertSame('', $report->issues[1]['alt']);
    }

    public function test_it_handles_malformed_html_gracefully(): void
    {
        $html = '<div><img src="/ok.jpg" alt="ok"><img src="/bad.jpg"></div';
        $report = $this->validator->scanHtml($html);
        $this->assertSame(2, $report->totalImages);
        $this->assertSame(1, $report->missingAltCount);
    }
}
