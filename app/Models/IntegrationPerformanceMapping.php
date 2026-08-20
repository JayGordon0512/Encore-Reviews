<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationPerformanceMapping extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'show_mapping_id',
        'provider_id',
        'account_reference',
        'external_performance_id',
        'organisation_id',
        'show_id',
        'performance_id',
    ];

    public function showMapping(): BelongsTo
    {
        return $this->belongsTo(IntegrationShowMapping::class, 'show_mapping_id');
    }

    public function performance(): BelongsTo
    {
        return $this->belongsTo(Performance::class);
    }
}
