<?php

namespace App\Services;

class GeoLocationService
{
    /**
     * @return array{city: string, country: string}
     */
    public function locate(?string $ipAddress): array
    {
        return [
            'city' => 'Unknown',
            'country' => 'Unknown',
        ];
    }
}
