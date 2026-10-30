<?php

namespace App\Http\Controllers;

use App\Http\Resources\AnalystPickResource;
use App\Models\Betslip;
use App\Services\AnalystArchiveService;
use App\Services\AnalystPickService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnalystDashboardController extends Controller
{
    public function __construct(
        protected AnalystArchiveService $archive,
    ) {
    }

    public function index()
    {
        $user = auth()->user();

        $picks = Betslip::analystSlips()
            ->where('analyst_id', $user->id)
            ->with(['odds.market', 'odds.fixture.homeTeam', 'odds.fixture.awayTeam', 'odds.fixture.league'])
            ->orderByDesc('created_at')
            ->paginate(20);

        $publishedToday = Betslip::analystSlips()
            ->where('analyst_id', $user->id)
            ->whereDate('analyst_published_at', today())
            ->whereNull('analyst_unpublished_reason')
            ->exists();

        return Inertia::render('Analyst/Dashboard', [
            'picks' => AnalystPickResource::collection($picks)->resolve(),
            'pagination' => [
                'current_page' => $picks->currentPage(),
                'last_page' => $picks->lastPage(),
                'prev_url' => $picks->previousPageUrl(),
                'next_url' => $picks->nextPageUrl(),
            ],
            'record' => $this->archive->record($user->id),
            'published_today' => $publishedToday,
            'profile_url' => $user->analyst_slug ? route('analysts.show', $user->analyst_slug) : null,
        ]);
    }

    public function edit(Betslip $betslip)
    {
        $user = auth()->user();

        abort_if($betslip->analyst_id !== $user->id, 403);
        abort_if(!$betslip->isEditableAsAnalyst(), 403, 'This pick cannot be edited.');

        $betslip->load(['odds.market', 'odds.fixture.homeTeam', 'odds.fixture.awayTeam', 'odds.fixture.league']);

        return Inertia::render('Analyst/Edit', [
            'pick' => (new AnalystPickResource($betslip))->resolve(),
        ]);
    }

    public function update(Request $request, Betslip $betslip, AnalystPickService $service)
    {
        $validated = $request->validate([
            'analyst_title' => ['required', 'string', 'max:140'],
            'reasoning' => [
                'required',
                'string',
                function ($attr, $value, $fail) {
                    $words = str_word_count(strip_tags((string) $value));
                    if ($words < 100 || $words > 500)
                        $fail('Reasoning must be between 100 and 500 words.');
                }
            ],
            'legs' => ['required', 'array', 'min:1'],
            'legs.*.odd_id' => ['required', 'integer'],
            'legs.*.comment' => ['required', 'string', 'max:200'],
            'legs.*.confidence' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        try {
            $service->updateByAnalyst($betslip, $validated, $request->user());
        } catch (\DomainException $e) {
            return back()->withErrors(['general' => $e->getMessage()]);
        }

        return redirect()->route('analyst.dashboard')->with('success', 'Pick updated.');
    }

    public function unpublish(Betslip $betslip, AnalystPickService $service)
    {
        try {
            $service->unpublishByAnalyst($betslip, auth()->user());
        } catch (\DomainException $e) {
            return back()->withErrors(['general' => $e->getMessage()]);
        }

        return back()->with('success', 'Pick unpublished. You can publish again today.');
    }
}