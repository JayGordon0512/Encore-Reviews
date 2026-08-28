<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AudienceAttendance extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'organisation_id',
        'show_id',
        'performance_id',
        'contact_id',
        'audience_import_id',
        'source',
        'attendance_state',
        'status',
    ];

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function show(): BelongsTo
    {
        return $this->belongsTo(Show::class);
    }

    public function performance(): BelongsTo
    {
        return $this->belongsTo(Performance::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(ProtectedReviewerContact::class, 'contact_id');
    }

    public function audienceImport(): BelongsTo
    {
        return $this->belongsTo(AudienceImport::class);
    }
}
