<?php

namespace App\Http\Controllers;

use App\Http\Resources\AnalystPickResource;
use App\Models\Betslip;
use App\Models\User;
use App\Services\AnalystArchiveService;
use Inertia\Inertia;

class AnalystProfileController extends Controller
{
    public function __construct(
        protected AnalystArchiveService $archive,
    ) {
    }

    public function show(string $slug)
    {
        $analyst = User::where('analyst_slug', $slug)
            ->where('is_analyst', true)
            ->firstOrFail();

        $recentSettled = Betslip::publishedAnalystSlips()
            ->where('analyst_id', $analyst->id)
            ->whereIn('status', ['settled', 'voided'])
            ->with($this->eagerLoad())
            ->orderByDesc('settled_at')
            ->limit(10)
            ->get();

        $open = Betslip::publishedAnalystSlips()
            ->where('analyst_id', $analyst->id)
            ->whereIn('status', ['pending', 'underway'])
            ->with($this->eagerLoad())
            ->orderByDesc('analyst_published_at')
            ->get();

        return Inertia::render('Analysts/Show', [
            'analyst' => [
                'name' => $analyst->byline(),
                'slug' => $analyst->analyst_slug,
                'bio' => $analyst->bio,
                'avatar_url' => $analyst->profile_picture_url,
                'joined_at' => optional($analyst->created_at)->toIso8601String(),
            ],
            'record' => $this->archive->record($analyst->id),
            'last_30_days' => $this->archive->rollingRecord($analyst->id, 30),
            'open' => AnalystPickResource::collection($open)->resolve(),
            'recent_settled' => AnalystPickResource::collection($recentSettled)->resolve(),
            'meta' => [
                'og_title' => $analyst->byline() . ' — Analyst picks | Fainali',
                'og_description' => mb_substr((string) $analyst->bio, 0, 160) ?: 'Sports predictions from ' . $analyst->byline() . '.',
                'og_image' => '/og/analyst/' . $analyst->analyst_slug . '.png',
                'canonical' => url('/analysts/' . $analyst->analyst_slug),
            ],
        ]);
    }

    protected function eagerLoad(): array
    {
        return [
            'analyst',
            'odds.market',
            'odds.fixture.homeTeam',
            'odds.fixture.awayTeam',
            'odds.fixture.league',
        ];
    }
}