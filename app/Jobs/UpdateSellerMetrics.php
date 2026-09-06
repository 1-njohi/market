<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\SellerMetric;
use App\Services\SellerDashboardService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class UpdateSellerMetrics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function handle(SellerDashboardService $service)
    {
        // Use Cache::remember to get or compute the data.
        // TTL set to 24 hours (86400 seconds) – since the job runs daily,
        // this ensures fresh data is recomputed each day.
        $data = Cache::remember("seller_dashboard_{$this->user->id}", 86400, function () use ($service) {
            return $service->getPerformanceMetrics($this->user);
        });

        // Update the seller_metrics table with the same data.
        SellerMetric::updateOrCreate(
            ['user_id' => $this->user->id],
            [
                'win_rate' => $data['win_rate'],
                'roi' => $data['roi'],
                'total_sold' => $data['total_sold'],
                'total_revenue' => $data['total_revenue'],
                'avg_price' => $data['avg_price'],
                'avg_odds' => $data['avg_odds'],
                'avg_legs' => $data['avg_legs'],
                'follower_count' => $this->user->followers()->count(),
                'calculated_at' => now(),
            ]
        );
    }
}