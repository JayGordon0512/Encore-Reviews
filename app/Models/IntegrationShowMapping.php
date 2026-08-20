<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IntegrationShowMapping extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'organisation_mapping_id',
        'provider_id',
        'account_reference',
        'external_show_id',
        'organisation_id',
        'show_id',
    ];

    public function organisationMapping(): BelongsTo
    {
        return $this->belongsTo(IntegrationOrganisationMapping::class, 'organisation_mapping_id');
    }

    public function show(): BelongsTo
    {
        return $this->belongsTo(Show::class);
    }

    public function performanceMappings(): HasMany
    {
        return $this->hasMany(IntegrationPerformanceMapping::class, 'show_mapping_id');
    }
}
