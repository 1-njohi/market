<?php

namespace App\Http\Controllers;

use App\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ReferralDashboardController extends Controller
{
    public function __construct(
        protected ReferralService $referralService,
    ) {
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        $dashboard = $this->referralService->dashboardFor($user);

        if ($request->wantsJson()) {
            return response()->json(['dashboard' => $dashboard]);
        }

        return Inertia::render('Refer', [
            'dashboard' => $dashboard,
        ]);
    }
}