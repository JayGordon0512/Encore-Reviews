<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailgunDeliveryEvent extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'delivery_id', 'provider_event_id', 'signature_token_digest', 'event_type',
        'severity', 'reason_code', 'outcome', 'event_at', 'received_at',
    ];

    protected function casts(): array
    {
        return ['event_at' => 'datetime', 'received_at' => 'datetime'];
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(ReviewInvitationDelivery::class, 'delivery_id');
    }
}
