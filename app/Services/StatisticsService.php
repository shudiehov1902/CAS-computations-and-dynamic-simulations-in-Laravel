<?php

namespace App\Services;

use App\Models\AnimationUsage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use InvalidArgumentException;

class StatisticsService
{
    public const TYPE_PENDULUM = 'pendulum';

    public const TYPE_BALL_BEAM = 'ball_beam';

    private const LABELS = [
        self::TYPE_PENDULUM => 'Inverted Pendulum',
        self::TYPE_BALL_BEAM => 'Ball and Beam',
    ];

    public function __construct(
        private readonly GeoLocationService $geoLocationService,
    ) {
    }

    public function recordUsage(string $animationType, string $userToken, ?string $ipAddress): ?AnimationUsage
    {
        $normalizedType = $this->normalizeAnimationType($animationType);

        if ($normalizedType === null) {
            throw new InvalidArgumentException("Unsupported animation type [{$animationType}].");
        }

        if ($this->wasRecentlyCounted($normalizedType, $userToken)) {
            return null;
        }

        $location = $this->geoLocationService->locate($ipAddress);

        return AnimationUsage::create([
            'animation_type' => $normalizedType,
            'user_token' => $userToken,
            'ip_address' => $ipAddress,
            'city' => $location['city'],
            'country' => $location['country'],
            'used_at' => now(),
        ]);
    }

    /**
     * @return array<int, array{animation_type: string, label: string, count: int}>
     */
    public function summary(): array
    {
        return collect(self::LABELS)
            ->map(fn (string $label, string $animationType): array => [
                'animation_type' => $animationType,
                'label' => $label,
                'count' => AnimationUsage::query()
                    ->where('animation_type', $animationType)
                    ->count(),
            ])
            ->values()
            ->all();
    }

    public function details(string $animationType, int $perPage = 20): LengthAwarePaginator
    {
        $normalizedType = $this->normalizeAnimationType($animationType);

        if ($normalizedType === null) {
            throw new InvalidArgumentException("Unsupported animation type [{$animationType}].");
        }

        return AnimationUsage::query()
            ->where('animation_type', $normalizedType)
            ->orderByDesc('used_at')
            ->orderByDesc('id')
            ->paginate($this->normalizePerPage($perPage));
    }

    public function normalizeAnimationType(string $animationType): ?string
    {
        return match ($animationType) {
            self::TYPE_PENDULUM => self::TYPE_PENDULUM,
            self::TYPE_BALL_BEAM, 'ball-beam' => self::TYPE_BALL_BEAM,
            default => null,
        };
    }

    public function labelFor(string $animationType): ?string
    {
        $normalizedType = $this->normalizeAnimationType($animationType);

        return $normalizedType === null ? null : self::LABELS[$normalizedType];
    }

    public function intervalMinutes(): int
    {
        return max(0, (int) config('cas.statistics_interval_minutes', 10));
    }

    private function wasRecentlyCounted(string $animationType, string $userToken): bool
    {
        $intervalMinutes = $this->intervalMinutes();

        if ($intervalMinutes === 0) {
            return false;
        }

        return AnimationUsage::query()
            ->where('animation_type', $animationType)
            ->where('user_token', $userToken)
            ->where('used_at', '>=', now()->subMinutes($intervalMinutes))
            ->exists();
    }

    private function normalizePerPage(int $perPage): int
    {
        if ($perPage < 1) {
            return 20;
        }

        return min($perPage, 100);
    }
}
