<?php

namespace App\Http\Controllers;

use App\Models\AnimationUsage;
use App\Services\StatisticsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    public function page(Request $request, StatisticsService $statisticsService): View
    {
        $selectedAnimation = $statisticsService->normalizeAnimationType((string) $request->query('animation', StatisticsService::TYPE_PENDULUM))
            ?? StatisticsService::TYPE_PENDULUM;

        $details = $statisticsService
            ->details($selectedAnimation, $this->perPage($request))
            ->withQueryString();

        return view('pages.statistics', [
            'statistics' => $statisticsService->summary(),
            'selectedAnimation' => $selectedAnimation,
            'selectedLabel' => $this->localizedLabel($selectedAnimation),
            'details' => $details,
            'intervalMinutes' => $statisticsService->intervalMinutes(),
        ]);
    }

    public function index(StatisticsService $statisticsService): JsonResponse
    {
        return response()->json([
            'data' => $statisticsService->summary(),
            'interval_minutes' => $statisticsService->intervalMinutes(),
        ]);
    }

    public function show(string $animation, Request $request, StatisticsService $statisticsService): JsonResponse
    {
        $animationType = $statisticsService->normalizeAnimationType($animation);

        if ($animationType === null) {
            return response()->json([
                'message' => 'Animation statistics not found.',
            ], 404);
        }

        $details = $statisticsService->details($animationType, $this->perPage($request));

        return response()->json([
            'animation_type' => $animationType,
            'label' => $statisticsService->labelFor($animationType),
            'data' => $details->getCollection()
                ->map(fn (AnimationUsage $usage): array => $this->formatUsage($usage))
                ->values(),
            'meta' => [
                'current_page' => $details->currentPage(),
                'per_page' => $details->perPage(),
                'total' => $details->total(),
                'last_page' => $details->lastPage(),
            ],
        ]);
    }

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 20);

        if ($perPage < 1) {
            return 20;
        }

        return min($perPage, 100);
    }

    /**
     * @return array{id: int, used_at: string|null, user_token: string, ip_address: string|null, city: string, country: string}
     */
    private function formatUsage(AnimationUsage $usage): array
    {
        return [
            'id' => $usage->id,
            'used_at' => $usage->used_at?->toIso8601String(),
            'user_token' => $usage->user_token,
            'ip_address' => $usage->ip_address,
            'city' => $usage->city,
            'country' => $usage->country,
        ];
    }

    private function localizedLabel(string $animationType): string
    {
        return match ($animationType) {
            StatisticsService::TYPE_BALL_BEAM => __('messages.ball_beam_heading'),
            default => __('messages.pendulum_heading'),
        };
    }
}
