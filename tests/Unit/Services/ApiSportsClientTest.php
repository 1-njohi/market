<?php

namespace Tests\Unit\Services;

use App\Services\ApiSportsClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApiSportsClientTest extends TestCase
{
    public function test_returns_response_array_on_success(): void
    {
        Http::fake([
            '*' => Http::response(['response' => [['fixture' => ['id' => 1]]]], 200),
        ]);

        $client = new ApiSportsClient('https://example.test', 'test-key');
        $result = $client->fixturesByDate('2026-10-05');

        $this->assertSame([['fixture' => ['id' => 1]]], $result);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/fixtures')
                && str_contains($request->url(), 'date=2026-10-05')
                && $request->hasHeader('x-apisports-key', 'test-key');
        });
    }

    public function test_throws_when_api_returns_error_status(): void
    {
        Http::fake(['*' => Http::response('boom', 500)]);

        $client = new ApiSportsClient('https://example.test', 'test-key');

        $this->expectException(\RuntimeException::class);
        $client->fixturesByDate('2026-10-05');
    }

    public function test_returns_empty_array_when_response_key_missing(): void
    {
        Http::fake(['*' => Http::response(['results' => 0], 200)]);

        $client = new ApiSportsClient('https://example.test', 'test-key');

        $this->assertSame([], $client->fixturesByDate('2026-10-05'));
    }

    public function test_fixtures_by_ids_joins_with_dash(): void
    {
        Http::fake(['*' => Http::response(['response' => []], 200)]);

        $client = new ApiSportsClient('https://example.test', 'test-key');
        $client->fixturesByIds([111, 222, 333]);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'ids=111-222-333'));
    }

    public function test_odds_by_fixture_passes_fixture_id(): void
    {
        Http::fake(['*' => Http::response(['response' => []], 200)]);

        $client = new ApiSportsClient('https://example.test', 'test-key');
        $client->oddsByFixture(9876);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/odds')
            && str_contains($request->url(), 'fixture=9876'));
    }
}