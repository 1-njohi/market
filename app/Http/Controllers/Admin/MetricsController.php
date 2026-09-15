<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BusinessMetricsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MetricsController extends Controller
{
    public function __construct(
        protected BusinessMetricsService $metrics,
    ) {
    }

    public function index(Request $request)
    {
        $days = (int) $request->input('days', 7);

        if (! in_array($days, BusinessMetricsService::WINDOWS, true)) {
            $days = 7;
        }

        return Inertia::render('admin/metrics/Index', [
            'metrics' => $this->metrics->summary($days),
            'windows' => BusinessMetricsService::WINDOWS,
            'filters' => ['days' => $days],
        ]);
    }

    /**
     * Force-refresh every window. Used by an admin who doesn't want
     * to wait for the next scheduled warm.
     */
    public function refresh(Request $request)
    {
        foreach (BusinessMetricsService::WINDOWS as $days) {
            $this->metrics->refresh($days);
        }

        return back()->with('success', 'Metrics refreshed.');
    }
}