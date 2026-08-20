<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ReviewConsentEvidence extends Model
{
    use HasUuids;

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'provider_id', 'credential_id', 'organisation_id', 'account_reference',
        'provider_event_id', 'provider_booking_id', 'purpose', 'policy_version',
        'captured_at', 'evidence_digest', 'created_at',
    ];

    protected $hidden = ['evidence_digest'];

    protected function casts(): array
    {
        return ['captured_at' => 'datetime', 'created_at' => 'datetime'];
    }
}
