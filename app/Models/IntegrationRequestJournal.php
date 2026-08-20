<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationRequestJournal extends Model
{
    use HasUuids;

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'credential_id',
        'provider_id',
        'idempotency_record_id',
        'credential_key_fingerprint',
        'operation',
        'method',
        'path',
        'body_digest',
        'auth_outcome',
        'failure_code',
        'correlation_id',
        'received_at',
        'completed_at',
        'response_status',
    ];

    protected $hidden = [
        'credential_key_fingerprint',
        'body_digest',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'completed_at' => 'datetime',
            'response_status' => 'integer',
        ];
    }

    public function credential(): BelongsTo
    {
        return $this->belongsTo(IntegrationCredential::class, 'credential_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(IntegrationProvider::class, 'provider_id');
    }

    public function idempotencyRecord(): BelongsTo
    {
        return $this->belongsTo(IntegrationIdempotencyRecord::class, 'idempotency_record_id');
    }
}
