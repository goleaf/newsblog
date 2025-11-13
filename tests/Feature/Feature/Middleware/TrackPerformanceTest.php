<?php

namespace Tests\Feature\Feature\Middleware;

use App\Http\Middleware\TrackPerformance;
use App\Services\PerformanceMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Mockery;
use Tests\TestCase;

class TrackPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_tracks_page_load_time(): void
    {
        $performanceMetrics = Mockery::mock(PerformanceMetricsService::class);
        $performanceMetrics->shouldReceive('trackPageLoad')
            ->once()
            ->withArgs(function ($route, $loadTime) {
                return is_string($route) && is_numeric($loadTime) && $loadTime >= 0;
            });

        $middleware = new TrackPerformance($performanceMetrics);
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('Test content');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_middleware_does_not_interfere_with_response(): void
    {
        $performanceMetrics = Mockery::mock(PerformanceMetricsService::class);
        $performanceMetrics->shouldReceive('trackPageLoad')->once();

        $middleware = new TrackPerformance($performanceMetrics);
        $request = Request::create('/test', 'GET');

        $expectedContent = 'Test response content';
        $response = $middleware->handle($request, function ($req) use ($expectedContent) {
            return new Response($expectedContent);
        });

        $this->assertEquals($expectedContent, $response->getContent());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
