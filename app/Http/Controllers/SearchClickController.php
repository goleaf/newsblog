<?php

namespace App\Http\Controllers;

use App\Services\SearchAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchClickController extends Controller
{
    public function __construct(
        protected SearchAnalyticsService $searchAnalytics
    ) {
    }

    /**
     * Track a search result click.
     * Requirement: 16.2
     */
    public function track(\App\Http\Requests\TrackSearchClickRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Log the click (Requirement 16.2)
        $this->searchAnalytics->logClick(
            $validated['search_log_id'],
            $validated['post_id'],
            $validated['position']
        );

        return response()->json([
            'success' => true,
            'message' => 'Click tracked successfully',
        ]);
    }
}
