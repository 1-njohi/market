<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalystPickResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $slip = $this->resource;

        return [
            'id'                    => $slip->id,
            'code'                  => $slip->code,
            'url'                   => '/analyst-picks/' . $slip->id,

            'analyst_title'         => $slip->analyst_title,
            'reasoning'             => $slip->reasoning,
            'post_settlement_note'  => $slip->post_settlement_note,

            'total_odds'            => (float) $slip->total_odds,
            'status'                => $slip->status,
            'is_winner'             => (bool) $slip->is_winner,
            'profit_units'          => $slip->analystProfitUnits(),

            'analyst_published_at'  => optional($slip->analyst_published_at)->toIso8601String(),
            'settled_at'            => optional($slip->settled_at)->toIso8601String(),

            'is_edited'             => (bool) $slip->is_edited,
            'revision_count'        => (int) $slip->revision_count,

            'analyst'               => [
                'name'       => $slip->analyst?->byline() ?? 'Fainali Analyst',
                'slug'       => $slip->analyst?->analyst_slug,
                'avatar_url' => $slip->analyst?->profile_picture_url,
            ],

            'legs'                  => $this->legs($slip),
        ];
    }

    protected function legs($slip): array
    {
        return $slip->odds->map(function ($odd) {
            return [
                'odd_id'     => $odd->id,
                'selection'  => $odd->value,
                'market'     => $odd->market?->name ?? 'Market',
                'odds'       => (float) ($odd->pivot->odd_value_at_time ?? $odd->odd),
                'comment'    => $odd->pivot->comment,
                'confidence' => $odd->pivot->confidence !== null ? (int) $odd->pivot->confidence : null,
                'status'     => $odd->pivot->status ?? 'pending',
                'fixture'    => [
                    'id'         => $odd->fixture?->id,
                    'home_team'  => $odd->fixture?->homeTeam?->name ?? 'Home',
                    'away_team'  => $odd->fixture?->awayTeam?->name ?? 'Away',
                    'league'     => $odd->fixture?->league?->name,
                    'kickoff'    => $odd->fixture?->date
                        ? \Carbon\Carbon::parse($odd->fixture->date)->toIso8601String()
                        : null,
                ],
            ];
        })->values()->all();
    }
}