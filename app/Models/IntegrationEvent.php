<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class IntegrationEvent extends Model
{
    use HasUuids;

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'provider',
        'event_type',
        'external_event_id',
        'payload_hash',
        'received_at',
        'processed_at',
        'status',
        'attempts',
        'error_message',
        'correlation_id',
        'response_body',
        'response_status',
        'response_expires_at',
    ];

    protected $hidden = [
        'response_body',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
        'response_expires_at' => 'datetime',
        'attempts' => 'integer',
        'response_status' => 'integer',
    ];
}
