<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationIdempotencyRecord extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'credential_id',
        'operation',
        'idempotency_key',
        'request_digest',
        'status',
        'outcome_type',
        'outcome_id',
        'first_correlation_id',
        'last_correlation_id',
        'response_status',
    ];

    protected $hidden = [
        'request_digest',
    ];

    protected function casts(): array
    {
        return [
            'response_status' => 'integer',
        ];
    }

    public function credential(): BelongsTo
    {
        return $this->belongsTo(IntegrationCredential::class, 'credential_id');
    }
}
