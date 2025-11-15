<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MonitoringService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonitoringController extends Controller
{
    public function __construct(
        private MonitoringService $monitoring
    ) {}

    /**
     * Display monitoring dashboard.
     */
    public function index(): View
    {
        $metrics = $this->monitoring->getMetricsSnapshot();
        $alerts = $this->monitoring->checkAlertThresholds();

        // Calculate derived metrics
        $dntTotal = $metrics['dnt']['enabled'] + $metrics['dnt']['disabled'];
        $dntRate = $dntTotal > 0
            ? round(($metrics['dnt']['enabled'] / $dntTotal) * 100, 2)
            : 0;

        $searchTotal = $metrics['search']['total'];
        $zeroResultRate = $searchTotal > 0
            ? round(($metrics['search']['zero_results'] / $searchTotal) * 100, 2)
            : 0;

        $engagementTotal = $metrics['engagement']['total'];
        $authenticatedRate = $engagementTotal > 0
            ? round(($metrics['engagement']['authenticated'] / $engagementTotal) * 100, 2)
            : 0;

        return view('admin.monitoring.index', compact(
            'metrics',
            'alerts',
            'dntRate',
            'zeroResultRate',
            'authenticatedRate'
        ));
    }

    /**
     * Reset metrics (for testing or periodic resets).
     */
    public function reset(Request $request)
    {
        $this->monitoring->resetMetrics();

        return redirect()
            ->route('admin.monitoring.index')
            ->with('success', 'Metrics have been reset');
    }
}
