<?php

namespace App\Jobs;

use App\Models\Fixture;
use App\Services\MarketSettlementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SettleFixtureJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $fixture;

    public $tries = 5;           // Max attempts
    public $backoff = [600, 1200, 1800, 3600]; // Retry after 10min, 20min, 30min, 1h

    public function __construct(Fixture $fixture)
    {
        $this->fixture = $fixture;
    }

    public function handle(MarketSettlementService $settlementService)
    {
        $matchData = $this->fetchMatchData();

        if (!$matchData) {
            // Match not finished or data missing – release back to queue
            Log::info("Match data not ready for fixture {$this->fixture->id}. Releasing job.");
            $this->release(600); // retry in 10 minutes
            return;
        }

        $settlementService->settleFixture($this->fixture, $matchData['response'][0]);

        Log::info("Fixture {$this->fixture->id} settled successfully.");
    }

    /**
     * Fetch match data from the external API or a local test endpoint.
     *
     * @return array|null
     */
    protected function fetchMatchData(): ?array
    {
        // If we have a test endpoint for local development, use it.
        if (config('app.env') !== 'local-tuna') {
            $apiResponse = [
                "get" => "fixtures",
                "parameters" => [
                    "id" => "867946"
                ],
                "errors" => [],
                "results" => 1,
                "paging" => [
                    "current" => 1,
                    "total" => 1
                ],
                "response" => [
                    [
                        "fixture" => [
                            "id" => 867946,
                            "referee" => "A. Taylor",
                            "timezone" => "UTC",
                            "date" => "2022-08-05T19:00:00+00:00",
                            "timestamp" => 1659726000,
                            "periods" => [
                                "first" => 1659726000,
                                "second" => 1659729600
                            ],
                            "venue" => [
                                "id" => 525,
                                "name" => "Selhurst Park",
                                "city" => "London"
                            ],
                            "status" => [
                                "long" => "Match Finished",
                                "short" => "FT",
                                "elapsed" => 90,
                                "extra" => null
                            ]
                        ],
                        "league" => [
                            "id" => 39,
                            "name" => "Premier League",
                            "country" => "England",
                            "logo" => "https://media.api-sports.io/football/leagues/39.png",
                            "flag" => "https://media.api-sports.io/flags/gb-eng.svg",
                            "season" => 2022,
                            "round" => "Regular Season - 1",
                            "standings" => true
                        ],
                        "teams" => [
                            "home" => [
                                "id" => 52,
                                "name" => "Crystal Palace",
                                "logo" => "https://media.api-sports.io/football/teams/52.png",
                                "winner" => false
                            ],
                            "away" => [
                                "id" => 42,
                                "name" => "Arsenal",
                                "logo" => "https://media.api-sports.io/football/teams/42.png",
                                "winner" => true
                            ]
                        ],
                        "goals" => [
                            "home" => 0,
                            "away" => 2
                        ],
                        "score" => [
                            "halftime" => [
                                "home" => 0,
                                "away" => 1
                            ],
                            "fulltime" => [
                                "home" => 0,
                                "away" => 2
                            ],
                            "extratime" => [
                                "home" => null,
                                "away" => null
                            ],
                            "penalty" => [
                                "home" => null,
                                "away" => null
                            ]
                        ],
                        "events" => [
                            [
                                "time" => [
                                    "elapsed" => 20,
                                    "extra" => null
                                ],
                                "team" => [
                                    "id" => 42,
                                    "name" => "Arsenal",
                                    "logo" => "https://media.api-sports.io/football/teams/42.png"
                                ],
                                "player" => [
                                    "id" => 127769,
                                    "name" => "Gabriel Martinelli"
                                ],
                                "assist" => [
                                    "id" => 641,
                                    "name" => "O. Zinchenko"
                                ],
                                "type" => "Goal",
                                "detail" => "Normal Goal",
                                "comments" => null
                            ],
                            [
                                "time" => [
                                    "elapsed" => 85,
                                    "extra" => null
                                ],
                                "team" => [
                                    "id" => 52,
                                    "name" => "Crystal Palace",
                                    "logo" => "https://media.api-sports.io/football/teams/52.png"
                                ],
                                "player" => [
                                    "id" => 67971,
                                    "name" => "M. Guéhi"
                                ],
                                "assist" => [
                                    "id" => null,
                                    "name" => null
                                ],
                                "type" => "Goal",
                                "detail" => "Own Goal",
                                "comments" => null
                            ]
                        ],
                        "lineups" => []
                    ]
                ]
            ];
            return $apiResponse;
        }

        // Production: call the real API-Sports endpoint
        $apiKey = config('services.api_sports.key');
        $baseUrl = config('services.api_sports.base_url');

        if (!$apiKey || !$baseUrl) {
            Log::error('API-Sports credentials missing.');
            return null;
        }

        $uri = $baseUrl . '/fixtures?id=' . $this->fixture->id_on_api;

        try {
            $response = Http::withHeaders([
                'x-apisports-key' => $apiKey,
            ])->get($uri);

            if (!$response->successful()) {
                Log::error('API-Sports request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();

            if (!empty($data['response'])) {
                $fixtureData = $data['response'][0];
                $statusShort = $fixtureData['fixture']['status']['short'] ?? '';

                if ($statusShort === 'FT') {
                    return $fixtureData;
                }

                Log::info("Fixture {$this->fixture->id_on_api} not finished yet (status: {$statusShort}).");
                return null;
            }

            Log::warning("No fixture data found for ID: {$this->fixture->id_on_api}");
            return null;

        } catch (\Exception $e) {
            Log::error('Exception while fetching match data: ' . $e->getMessage());
            return null;
        }
    }
}