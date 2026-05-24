<?php

namespace App\Models;

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
        'performance_id',
        'email_hash',
        'token_hash',
        'sent_at',
        'expires_at',
        'used_at',
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
    ];

    public function performance(): BelongsTo
    {
        return $this->belongsTo(Performance::class);
    }
}
