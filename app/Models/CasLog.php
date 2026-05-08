<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'command',
    'request_payload',
    'status',
    'output',
    'error_message',
    'ip_address',
    'user_token',
])]
class CasLog extends Model
{
    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
        ];
    }
}
