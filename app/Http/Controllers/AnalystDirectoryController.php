<?php

namespace App\Http\Controllers;

use App\Services\AnalystArchiveService;
use Inertia\Inertia;

class AnalystDirectoryController extends Controller
{
    public function index(AnalystArchiveService $archive)
    {
        $leaderboard = $archive->leaderboard(50);

        return Inertia::render('Analysts/Index', [
            'analysts' => collect($leaderboard)->map(fn($l) => [
                'id'         => $l['user']->id,
                'name'       => $l['user']->byline(),
                'slug'       => $l['user']->analyst_slug,
                'avatar_url' => $l['user']->profile_picture_url,
                'bio'        => $l['user']->bio,
                'record'     => $l['record'],
            ])->values()->all(),
            'combined_record' => $archive->combinedRecord(),
            'meta' => [
                'og_title'       => 'Analysts — Fainali',
                'og_description' => 'Every Fainali analyst, ranked by honest ROI.',
                'og_image'       => '/og/analyst-archive.png',
                'canonical'      => url('/analysts'),
            ],
        ]);
    }
}