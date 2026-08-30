<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewInvitation extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'eligibility_id',
        'performance_id',
        'email_hash',
        'token_hash',
        'token_version',
        'status',
        'sent_at',
        'expires_at',
        'used_at',
        'revoked_at',
        'revocation_reason',
        'provider_source',
        'provider_booking_id',
        'provider_ticket_id',
        'attendance_state',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'expires_at' => 'datetime',
        'sent_at' => 'datetime',
        'used_at' => 'datetime',
        'revoked_at' => 'datetime',
        'token_version' => 'integer',
    ];

    public function performance(): BelongsTo
    {
        return $this->belongsTo(Performance::class);
    }

    public function eligibility(): BelongsTo
    {
        return $this->belongsTo(ReviewEligibility::class);
    }

    public function scopeAvailable(Builder $query): void
    {
        $query->whereNotNull('sent_at')
            ->whereNull('used_at')
            ->whereNull('revoked_at')
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}
