<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class OutboxMessage extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'event_type', 'aggregate_type', 'aggregate_id', 'organisation_id',
        'provider_id', 'payload_version', 'payload', 'correlation_id', 'occurred_at',
        'available_at', 'claimed_at', 'published_at', 'dead_lettered_at', 'attempts',
        'last_error_code',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array', 'payload_version' => 'integer', 'attempts' => 'integer',
            'occurred_at' => 'datetime', 'available_at' => 'datetime',
            'claimed_at' => 'datetime', 'published_at' => 'datetime',
            'dead_lettered_at' => 'datetime',
        ];
    }
}
