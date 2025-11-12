<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PerformanceMetricsService;
use Illuminate\View\View;

class PerformanceController extends Controller
{
    public function __construct(private PerformanceMetricsService $performanceMetrics) {}

    /**
     * Display the performance dashboard
     */
    public function index(): View
    {
        $metrics = $this->performanceMetrics->getAllMetrics();

        return view('admin.performance.index', [
            'pageLoads' => $metrics['page_loads'],
            'slowQueries' => $metrics['slow_queries'],
            'cacheStats' => $metrics['cache_stats'],
            'memory' => $metrics['memory'],
        ]);
    }
}
