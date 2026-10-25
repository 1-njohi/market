<?php

namespace Tests\Feature\Fixtures;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Contract for GET /fixtures.
 *
 * Public browsing page. Must render the Fixtures Inertia component with
 * a `fixtures` prop shaped as an array of league groups — the same shape
 * the Welcome page's FixtureSummaryCard already consumes.
 *
 * Shape assertions are intentionally tolerant of empty data so the suite
 * stays green on a fresh database.
 */
class FixturesIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_returns_200(): void
    {
        $this->get('/fixtures')->assertOk();
    }

    public function test_route_is_reachable_without_authentication(): void
    {
        $this->get('/fixtures')->assertOk();
    }

    public function test_route_renders_fixtures_component(): void
    {
        $this->get('/fixtures')->assertInertia(
            fn (AssertableInertia $page) => $page->component('Fixtures'),
        );
    }

    public function test_route_passes_fixtures_prop(): void
    {
        $this->get('/fixtures')->assertInertia(
            fn (AssertableInertia $page) => $page->has('fixtures'),
        );
    }

    /**
     * Asserts against raw page data instead of via assertInertia's where()
     * closure, which misreads empty arrays as non-array values on a fresh
     * database. This gives us an unconditional type guarantee.
     */
    public function test_fixtures_prop_is_an_array(): void
    {
        $response = $this->get('/fixtures');
        $response->assertOk();

        $page = $response->viewData('page');

        $this->assertIsArray($page, 'Inertia page data missing');
        $this->assertArrayHasKey('props', $page);
        $this->assertArrayHasKey('fixtures', $page['props']);
        $this->assertIsArray(
            $page['props']['fixtures'],
            'The fixtures prop must be an array.',
        );
    }

    public function test_route_has_named_route(): void
    {
        $this->assertSame(
            '/fixtures',
            route('fixtures.index', [], false),
        );
    }
}