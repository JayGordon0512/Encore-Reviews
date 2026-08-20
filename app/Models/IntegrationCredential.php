<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IntegrationCredential extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'provider_id',
        'key_id',
        'account_reference',
        'secret_reference',
        'operation_scopes',
        'activated_at',
        'expires_at',
        'revoked_at',
        'rotated_from_id',
    ];

    protected $hidden = [
        'secret_reference',
    ];

    protected function casts(): array
    {
        return [
            'operation_scopes' => 'array',
            'activated_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(IntegrationProvider::class, 'provider_id');
    }

    public function rotatedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'rotated_from_id');
    }

    public function organisations(): BelongsToMany
    {
        return $this->belongsToMany(
            Organisation::class,
            'integration_credential_organisations',
            'credential_id',
            'organisation_id',
        )->withPivot('created_at');
    }

    public function requestNonces(): HasMany
    {
        return $this->hasMany(IntegrationRequestNonce::class, 'credential_id');
    }

    public function idempotencyRecords(): HasMany
    {
        return $this->hasMany(IntegrationIdempotencyRecord::class, 'credential_id');
    }
}
