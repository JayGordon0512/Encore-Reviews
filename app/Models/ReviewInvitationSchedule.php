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
        'eligibility_id', 'scheduled_for', 'status', 'suppression_reason', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return ['scheduled_for' => 'datetime', 'cancelled_at' => 'datetime'];
    }
}
