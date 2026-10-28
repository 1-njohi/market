<?php

namespace App\Http\Controllers;

use App\Http\Resources\AnalystPickResource;
use App\Models\Betslip;
use App\Services\AnalystArchiveService;
use Inertia\Inertia;

class AnalystPickController extends Controller
{
    public function __construct(
        protected AnalystArchiveService $archive,
    ) {}

    public function index()
    {
        $pending = Betslip::publishedAnalystSlips()
            ->whereIn('status', ['pending', 'underway'])
            ->with($this->eagerLoad())
            ->orderByDesc('analyst_published_at')
            ->get();

        $settled = Betslip::publishedAnalystSlips()
            ->whereIn('status', ['settled', 'voided'])
            ->with($this->eagerLoad())
            ->orderByDesc('settled_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('AnalystPicks/Index', [
            'pending'    => AnalystPickResource::collection($pending)->resolve(),
            'settled'    => AnalystPickResource::collection($settled)->resolve(),
            'pagination' => [
                'current_page' => $settled->currentPage(),
                'last_page'    => $settled->lastPage(),
                'per_page'     => $settled->perPage(),
                'total'        => $settled->total(),
                'prev_url'     => $settled->previousPageUrl(),
                'next_url'     => $settled->nextPageUrl(),
            ],
            'record'     => $this->archive->record(),
            'meta'       => [
                'og_title'       => 'Analyst Picks — Free sports predictions | Fainali',
                'og_description' => 'Editorial picks from Fainali\'s in-house analyst. Every pick shown, wins and losses. Honest ROI.',
                'og_image'       => '/og/analyst-archive.png',
                'canonical'      => url('/analyst-picks'),
            ],
        ]);
    }

    public function show(Betslip $betslip)
    {
        // Route binding scopes to is_analyst_slip + published. If we get
        // here, $betslip is a published analyst pick.
        $betslip->load($this->eagerLoad());

        $related = Betslip::publishedAnalystSlips()
            ->where('analyst_id', $betslip->analyst_id)
            ->where('id', '!=', $betslip->id)
            ->with($this->eagerLoad())
            ->orderByDesc('analyst_published_at')
            ->limit(3)
            ->get();

        $reasoningSnippet = mb_substr(strip_tags((string) $betslip->reasoning), 0, 160);

        return Inertia::render('AnalystPicks/Show', [
            'pick'    => (new AnalystPickResource($betslip))->resolve(),
            'related' => AnalystPickResource::collection($related)->resolve(),
            'record'  => $this->archive->record($betslip->analyst_id),
            'meta'    => [
                'og_title'       => ($betslip->analyst_title ?? 'Analyst pick') . ' — ' . ($betslip->analyst?->byline() ?? 'Fainali Analyst') . ' | Fainali',
                'og_description' => 'At ' . number_format((float) $betslip->total_odds, 2) . 'x odds. ' . $reasoningSnippet,
                'og_image'       => '/og/analyst-pick/' . $betslip->id . '.png',
                'canonical'      => url('/analyst-picks/' . $betslip->id),
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