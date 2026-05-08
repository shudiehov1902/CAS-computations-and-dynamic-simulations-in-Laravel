<?php

namespace Tests\Unit;

use App\Models\AnimationUsage;
use App\Services\StatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StatisticsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_usage_stores_unknown_location_and_prevents_recent_duplicate(): void
    {
        config(['cas.statistics_interval_minutes' => 10]);

        $userToken = (string) Str::uuid();
        $service = app(StatisticsService::class);

        $usage = $service->recordUsage('ball-beam', $userToken, '127.0.0.1');
        $duplicate = $service->recordUsage('ball_beam', $userToken, '127.0.0.1');

        $this->assertNotNull($usage);
        $this->assertNull($duplicate);
        $this->assertDatabaseHas('animation_usages', [
            'animation_type' => 'ball_beam',
            'user_token' => $userToken,
            'city' => 'Unknown',
            'country' => 'Unknown',
        ]);
        $this->assertDatabaseCount('animation_usages', 1);
    }

    public function test_summary_returns_counts_for_both_animations(): void
    {
        AnimationUsage::create([
            'animation_type' => 'pendulum',
            'user_token' => (string) Str::uuid(),
            'ip_address' => '127.0.0.1',
            'city' => 'Unknown',
            'country' => 'Unknown',
            'used_at' => now(),
        ]);

        $summary = app(StatisticsService::class)->summary();

        $this->assertSame('pendulum', $summary[0]['animation_type']);
        $this->assertSame(1, $summary[0]['count']);
        $this->assertSame('ball_beam', $summary[1]['animation_type']);
        $this->assertSame(0, $summary[1]['count']);
    }
}
