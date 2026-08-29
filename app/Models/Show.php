<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Show extends Model
{
    use HasFactory, HasUuids;

    public const SOURCE_MANUAL = 'encore_manual';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'title',
        'slug',
        'summary',
        'description',
        'genre',
        'primary_image_path',
        'primary_image_disk',
        'primary_image_storage_path',
        'status',
        'lifecycle_status',
        'reviews_locked',
        'ticket_url',
        'ticket_url_source',
        'ticket_url_last_synced_at',
        'provider_source',
        'provider_event_id',
        'organisation_id',
    ];

    protected $casts = [
        'ticket_url_last_synced_at' => 'datetime',
        'reviews_locked' => 'boolean',
    ];

    public function performances(): HasMany
    {
        return $this->hasMany(Performance::class);
    }

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function reviews(): HasManyThrough
    {
        return $this->hasManyThrough(Review::class, Performance::class);
    }

    public function audienceImports(): HasMany
    {
        return $this->hasMany(AudienceImport::class);
    }

    public function audienceAttendances(): HasMany
    {
        return $this->hasMany(AudienceAttendance::class);
    }

    public function artworkPath(): string
    {
        if (filled($this->primary_image_path)) {
            return $this->primary_image_path;
        }

        return $this->provider_source === self::SOURCE_MANUAL
            ? 'assets/encore-event-placeholder.svg'
            : 'assets/hero-show-bg.jpg';
    }
}
