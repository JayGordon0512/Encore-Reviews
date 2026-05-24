<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'performance_id',
        'reviewer_id',
        'rating',
        'would_recommend',
        'tags',
        'content',
        'verified',
        'verification_source',
        'moderation_status',
        'moderation_reason',
        'submitted_at',
        'edited_until',
        'ip_hash',
        'user_agent_hash',
    ];

    protected $casts = [
        'tags' => 'array',
        'submitted_at' => 'datetime',
        'edited_until' => 'datetime',
    ];

    public function performance(): BelongsTo
    {
        return $this->belongsTo(Performance::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Reviewer::class);
    }
}
