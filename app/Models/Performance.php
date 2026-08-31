<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Performance extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'show_id',
        'venue_id',
        'starts_at',
        'ends_at',
        'status',
        'provider_source',
        'provider_event_id',
        'provider_performance_id',
        'provider_updated_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'provider_updated_at' => 'datetime',
    ];

    public function show(): BelongsTo
    {
        return $this->belongsTo(Show::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(ReviewInvitation::class);
    }

    public function audienceImports(): HasMany
    {
        return $this->hasMany(AudienceImport::class);
    }

    public function audienceAttendances(): HasMany
    {
        return $this->hasMany(AudienceAttendance::class);
    }

    public function invitationSchedules(): HasManyThrough
    {
        return $this->hasManyThrough(
            ReviewInvitationSchedule::class,
            AudienceAttendance::class,
            'performance_id',
            'audience_attendance_id',
        );
    }
}
