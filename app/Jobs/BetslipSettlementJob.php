<?php

namespace App\Jobs;

use App\Models\Betslip;
use App\Services\BetslipSettlementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BetslipSettlementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 120, 300];

    public function __construct(public string $betslipId) {}

    public function handle(BetslipSettlementService $service): void
    {
        $betslip = Betslip::find($this->betslipId);

        if (!$betslip) {
            Log::warning("BetslipSettlementJob: betslip {$this->betslipId} not found.");
            return;
        }

        $service->settle($betslip);
    }
}