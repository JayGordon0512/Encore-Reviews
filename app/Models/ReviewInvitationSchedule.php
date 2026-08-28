<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ReviewInvitationSchedule extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'eligibility_id', 'correlation_id', 'scheduled_for', 'status', 'attempts',
        'claimed_at', 'issued_at', 'dead_lettered_at', 'suppression_reason',
        'last_error_code', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'datetime', 'attempts' => 'integer',
            'claimed_at' => 'datetime', 'issued_at' => 'datetime',
            'dead_lettered_at' => 'datetime', 'cancelled_at' => 'datetime',
        ];
    }
}
