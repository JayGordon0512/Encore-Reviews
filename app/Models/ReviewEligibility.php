<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewEligibility extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 'provider_id', 'credential_id', 'organisation_id', 'account_reference',
        'show_id', 'performance_id', 'contact_id', 'consent_evidence_id',
        'provider_event_id', 'provider_booking_id', 'purpose', 'admission_quantity',
        'status', 'occurred_at', 'withdrawn_at',
    ];

    protected function casts(): array
    {
        return [
            'admission_quantity' => 'integer',
            'occurred_at' => 'datetime',
            'withdrawn_at' => 'datetime',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(ProtectedReviewerContact::class, 'contact_id');
    }
}
