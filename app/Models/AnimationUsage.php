<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'animation_type',
    'user_token',
    'ip_address',
    'city',
    'country',
    'used_at',
])]
class AnimationUsage extends Model
{
    protected function casts(): array
    {
        return [
            'used_at' => 'datetime',
        ];
    }
}
