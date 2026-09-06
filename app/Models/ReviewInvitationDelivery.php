<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewInvitationDelivery extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'invitation_id', 'schedule_id', 'correlation_id', 'channel', 'status',
        'attempted_at', 'sent_at', 'provider_status_at', 'delivered_at',
        'failed_at', 'complained_at', 'error_code',
    ];

    protected function casts(): array
    {
        return [
            'attempted_at' => 'datetime', 'sent_at' => 'datetime',
            'provider_status_at' => 'datetime', 'delivered_at' => 'datetime',
            'failed_at' => 'datetime', 'complained_at' => 'datetime',
        ];
    }

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(ReviewInvitation::class);
    }
}
