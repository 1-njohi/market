<?php

namespace Tests\Feature;

use Tests\TestCase;

class ScaffoldingRetiredTest extends TestCase
{
    public function test_settle_fixture_job_class_no_longer_exists(): void
    {
        $this->assertFalse(
            class_exists(\App\Jobs\SettleFixtureJob::class),
            'SettleFixtureJob should be retired — use fixtures:settle command instead.'
        );
    }

    public function test_api_settle_route_is_not_registered(): void
    {
        $routes = collect(app('router')->getRoutes())
            ->map(fn ($r) => $r->uri())
            ->all();

        $this->assertNotContains('api/settle', $routes);
    }
}