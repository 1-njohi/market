<?php

namespace App\Http\Controllers;

use App\Services\FixtureService;
use Inertia\Inertia;

class FixturesController extends Controller
{
    public function __construct(
        protected FixtureService $fixtureService,
    ) {
    }

    /**
     * Public fixtures browse page.
     *
     * Renders the standalone /fixtures route with the same league-grouped
     * data shape the Welcome page uses, so the FixtureList component
     * behaves identically on both pages.
     */
    public function index()
    {
        return Inertia::render('Fixtures', [
            'fixtures' => $this->fixtureService->leaguesWithUpcomingFixtures(),
        ]);
    }
}