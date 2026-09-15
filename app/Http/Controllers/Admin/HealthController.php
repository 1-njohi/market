<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SystemHealthService;
use Inertia\Inertia;

class HealthController extends Controller
{
    public function __construct(
        protected SystemHealthService $health,
    ) {
    }

    public function index()
    {
        return Inertia::render('admin/health/Index', [
            'health' => $this->health->all(),
        ]);
    }
}