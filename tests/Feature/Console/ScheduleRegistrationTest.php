<?php

namespace Tests\Feature\Console;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class ScheduleRegistrationTest extends TestCase
{
    public function test_fixtures_sync_upcoming_runs_daily_at_03_utc(): void
    {
        $event = $this->findScheduledCommand('fixtures:sync-upcoming');
        $this->assertNotNull($event, 'fixtures:sync-upcoming is not scheduled.');
        $this->assertSame('0 3 * * *', $event->expression);
    }

    public function test_odds_sync_upcoming_runs_hourly(): void
    {
        $event = $this->findScheduledCommand('odds:sync-upcoming');
        $this->assertNotNull($event, 'odds:sync-upcoming is not scheduled.');
        $this->assertSame('0 * * * *', $event->expression);
    }

    public function test_fixtures_sync_live_runs_every_three_minutes(): void
    {
        $event = $this->findScheduledCommand('fixtures:sync-live');
        $this->assertNotNull($event, 'fixtures:sync-live is not scheduled.');
        $this->assertSame('*/3 * * * *', $event->expression);
    }

    public function test_fixtures_settle_runs_every_two_minutes(): void
    {
        $event = $this->findScheduledCommand('fixtures:settle');
        $this->assertNotNull($event, 'fixtures:settle is not scheduled.');
        $this->assertSame('*/2 * * * *', $event->expression);
    }

    public function test_every_sync_and_settle_command_has_overlap_and_single_server_guards(): void
    {
        $commands = [
            'fixtures:sync-upcoming',
            'odds:sync-upcoming',
            'fixtures:sync-live',
            'fixtures:settle',
        ];

        foreach ($commands as $command) {
            $event = $this->findScheduledCommand($command);

            $this->assertNotNull($event, "Command {$command} is not scheduled.");
            $this->assertTrue(
                $event->withoutOverlapping,
                "Command {$command} is missing withoutOverlapping()."
            );
            $this->assertTrue(
                $event->onOneServer,
                "Command {$command} is missing onOneServer()."
            );
        }
    }

    // ------------------------------------------------------------------
    // Helpers — no test_ prefix
    // ------------------------------------------------------------------

    private function findScheduledCommand(string $command): ?Event
    {
        foreach (app(Schedule::class)->events() as $event) {
            if (str_contains($event->command ?? '', $command)) {
                return $event;
            }
        }

        return null;
    }
}