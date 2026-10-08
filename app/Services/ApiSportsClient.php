<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ApiSportsClient
{
    public function __construct(
        protected ?string $baseUrl = null,
        protected ?string $apiKey = null,
    ) {
        $this->baseUrl = $baseUrl ?? (string) config('services.api_sports.base_url');
        $this->apiKey  = $apiKey  ?? (string) config('services.api_sports.key');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fixturesByDate(string $date): array
    {
        $f = $this->get('/fixtures', ['date' => $date]);

        return $f;
    }

    /**
     * @param  int[]  $ids
     * @return array<int, array<string, mixed>>
     */
    public function fixturesByIds(array $ids): array
    {
        $f = $this->get('/fixtures', ['ids' => implode('-', $ids)]);
        return $f;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function oddsByFixture(int $fixtureId): array
    {
        return $this->get('/odds', ['fixture' => (string) $fixtureId]);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<int, array<string, mixed>>
     */
    protected function get(string $path, array $query): array
    {
        $response = $this->http()->get($this->baseUrl . $path, $query);

        if (! $response->successful()) {
            throw new RuntimeException(
                "API-Sports request failed: {$path} status={$response->status()}"
            );
        }

        return $response->json('response', []) ?? [];
    }

    protected function http(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withHeaders(['x-apisports-key' => $this->apiKey]);
    }
}