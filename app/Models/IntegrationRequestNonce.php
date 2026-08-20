<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationRequestNonce extends Model
{
    use HasUuids;

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'credential_id',
        'nonce',
        'request_timestamp',
        'received_at',
        'expires_at',
        'correlation_id',
    ];

    protected function casts(): array
    {
        return [
            'request_timestamp' => 'datetime',
            'received_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function credential(): BelongsTo
    {
        return $this->belongsTo(IntegrationCredential::class, 'credential_id');
    }
}
