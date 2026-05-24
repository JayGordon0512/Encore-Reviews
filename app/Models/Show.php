<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Show extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'title',
        'slug',
        'summary',
        'description',
        'genre',
        'primary_image_path',
        'status',
        'ticket_url',
        'ticket_url_source',
        'ticket_url_last_synced_at',
        'provider_source',
        'provider_event_id',
    ];

    protected $casts = [
        'ticket_url_last_synced_at' => 'datetime',
    ];

    public function performances(): HasMany
    {
        return $this->hasMany(Performance::class);
    }
}
