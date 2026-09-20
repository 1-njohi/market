<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Jobs\SettleFixtureJob;
use App\Models\Fixture;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\MpesaWithdrawalCallbackController;
use App\Http\Controllers\WithdrawalController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/withdrawals', [WithdrawalController::class, 'store']);

Route::post('/odds', function (Request $request) {
    // Capture dynamic request inputs or fall back to your example parameters
    $fixtureId = $request->input('fixture', '326090');
    $bookmakerId = $request->input('bookmaker', '6');

    // Definition of static markets and their respective static values
    $staticMarkets = [
        ["id" => 38, "name" => "Exact Goals Number", "values" => [4, 3, 2, "more 8", 7, "more 5", 6, 5, 0, 1]],
        ["id" => 20, "name" => "Double Chance - First Half", "values" => ["Home/Draw", "Home/Away", "Draw/Away"]],
        ["id" => 17, "name" => "Total - Away", "values" => ["Under 2.5", "Over 2.5", "Under 1.5", "Over 1.5"]],
        ["id" => 16, "name" => "Total - Home", "values" => ["Under 2.5", "Over 2.5", "Under 1.5", "Over 1.5"]],
        ["id" => 22, "name" => "Odd/Even - First Half", "values" => ["Even", "Odd"]],
        ["id" => 21, "name" => "Odd/Even", "values" => ["Even", "Odd"]],
        ["id" => 34, "name" => "Both Teams Score - First Half", "values" => ["No", "Yes"]],
        ["id" => 32, "name" => "Win Both Halves", "values" => ["Away", "Draw", "Home"]],
        ["id" => 12, "name" => "Double Chance", "values" => ["Draw/Away", "Home/Away", "Home/Draw"]],
        ["id" => 10, "name" => "Exact Score", "values" => ["3:4", "2:4", "2:3", "1:4", "1:3", "1:2", "0:4", "4:1", "4:0", "3:2", "3:1", "3:0", "2:1", "2:0", "1:0", "4:2", "4:3", "0:3", "0:2", "0:1", "4:4", "3:3", "2:2", "1:1", "0:0"]],
        ["id" => 13, "name" => "First Half Winner", "values" => ["Home", "Draw", "Away"]],
        ["id" => 15, "name" => "Team To Score Last", "values" => ["No goal", "Away", "Home"]],
        ["id" => 14, "name" => "Team To Score First", "values" => ["Away", "Draw", "Home"]],
        ["id" => 46, "name" => "Exact Goals Number - First Half", "values" => ["more 3", 0, 1, 2]],
        ["id" => 25, "name" => "Result/Total Goals", "values" => ["Home/Over 2.5", "Away/Under 3.5", "Home/Under 3.5", "Away/Over 3.5", "Home/Over 3.5", "Away/Over 2.5", "Home/Under 2.5", "Away/Under 2.5"]],
        ["id" => 24, "name" => "Results/Both Teams Score", "values" => ["Away/No", "Draw/No", "Home/No", "Away/Yes", "Draw/Yes", "Home/Yes"]],
        ["id" => 44, "name" => "Away Team Score a Goal", "values" => ["No", "Yes"]],
        ["id" => 43, "name" => "Home Team Score a Goal", "values" => ["No", "Yes"]],
        ["id" => 40, "name" => "Home Team Exact Goals Number", "values" => [1, 2, 0, "more 3"]],
        ["id" => 42, "name" => "Second Half Exact Goals Number", "values" => ["more 3", 0, 1, 2]],
        ["id" => 41, "name" => "Away Team Exact Goals Number", "values" => ["more 3", 0, 1, 2]],
        ["id" => 7, "name" => "HT/FT Double", "values" => ["Home/Home", "Draw/Draw", "Draw/Away", "Home/Away", "Home/Draw", "Away/Away", "Away/Draw", "Away/Home", "Draw/Home"]],
        ["id" => 26, "name" => "Goals Over/Under - Second Half", "values" => ["Under 3.5", "Over 3.5", "Over 1.5", "Under 1.5", "Under 0.5", "Over 0.5", "Under 1.5", "Over 2.5"]],
        ["id" => 6, "name" => "Goals Over/Under First Half", "values" => ["Under 0.5", "Over 0.5", "Under 2.5", "Over 2.5", "Under 1.5", "Over 1.5", "Under 3.5", "Over 3.5"]],
        ["id" => 5, "name" => "Goals Over/Under", "values" => ["Under 5.5", "Over 3.5", "Under 3.5", "Over 1.5", "Over 5.5", "Under 0.5", "Over 0.5", "Under 2.5", "Over 2.5", "Under 4.5", "Over 4.5", "Under 1.5"]],
        ["id" => 3, "name" => "Second Half Winner", "values" => ["Away", "Draw", "Home"]],
        ["id" => 2, "name" => "Home/Away", "values" => ["Away", "Home"]],
        ["id" => 1, "name" => "Match Winner", "values" => ["Away", "Draw", "Home"]],
        ["id" => 9, "name" => "Handicap Result", "values" => ["Away -2", "Draw -2", "Home -2", "Home -1", "Away +2", "Draw +2", "Home +2", "Draw +1", "Away +1", "Home +1", "Draw -1", "Away -1"]],
        ["id" => 30, "name" => "Win to Nil - Away", "values" => ["Yes", "No"]],
        ["id" => 29, "name" => "Win to Nil - Home", "values" => ["No", "Yes"]],
        ["id" => 8, "name" => "Both Teams Score", "values" => ["No", "Yes"]]
    ];

    $processedBets = [];

    // Loop through markets to populate data transformations with random decimal odds
    foreach ($staticMarkets as $market) {
        $marketValues = [];
        foreach ($market['values'] as $value) {
            // Generates a floating point value between 1.10 and 15.00
            $randomOdd = mt_rand(110, 1500) / 100;

            $marketValues[] = [
                "value" => $value,
                "odd" => number_format($randomOdd, 2, '.', '')
            ];
        }

        $processedBets[] = [
            "id" => $market['id'],
            "name" => $market['name'],
            "values" => $marketValues
        ];
    }

    // Construct the wrapper matching API-Sports response signatures
    $apiResponse = [
        "get" => "odds",
        "parameters" => [
            "fixture" => (string) $fixtureId,
            "bookmaker" => (string) $bookmakerId
        ],
        "errors" => [],
        "results" => 1,
        "paging" => [
            "current" => 1,
            "total" => 1
        ],
        "response" => [
            [
                "league" => [
                    "id" => 116,
                    "name" => "Vysshaya Liga",
                    "country" => "Belarus",
                    "logo" => "https://media.api-sports.io/football/leagues/116.png",
                    "flag" => "https://media.api-sports.io/flags/by.svg",
                    "season" => (int) date('Y')
                ],
                "fixture" => [
                    "id" => (int) $fixtureId,
                    "timezone" => "UTC",
                    "date" => now()->toIso8601String(),
                    "timestamp" => now()->timestamp
                ],
                "update" => now()->toIso8601String(),
                "bookmakers" => [
                    [
                        "id" => (int) $bookmakerId,
                        "name" => "Bwin",
                        "bets" => $processedBets
                    ]
                ]
            ]
        ]
    ];



    return response()->json($apiResponse);
});

Route::get('/result', function (Request $request) {
    // Sample API response structure
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

    return response()->json($apiResponse);
});

Route::get('/settle', function (Request $request) {
    $fixture = Fixture::first();

    SettleFixtureJob::dispatchSync($fixture);

    return response()->json(['message' => 'Settlement job dispatched']);
});

Route::prefix('mpesa/b2c')->group(function () {
    Route::post('/result', [MpesaWithdrawalCallbackController::class, 'result']);
    Route::post('/timeout', [MpesaWithdrawalCallbackController::class, 'timeout']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/withdrawals', [WithdrawalController::class, 'store']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
});
