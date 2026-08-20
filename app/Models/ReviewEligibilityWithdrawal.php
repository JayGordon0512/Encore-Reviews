<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ReviewEligibilityWithdrawal extends Model
{
    use HasUuids;

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'provider_id', 'credential_id', 'eligibility_id', 'account_reference',
        'provider_event_id', 'original_eligibility_event_id', 'provider_booking_id',
        'purpose', 'withdrawn_at', 'created_at',
    ];

    protected function casts(): array
    {
        return ['withdrawn_at' => 'datetime', 'created_at' => 'datetime'];
    }
}
