<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AudienceImport extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'organisation_id',
        'show_id',
        'performance_id',
        'imported_by',
        'source_file_name',
        'rows_total',
        'rows_imported',
        'rows_skipped',
        'status',
        'attendance_confirmed_at',
        'correlation_id',
    ];

    protected function casts(): array
    {
        return [
            'rows_total' => 'integer',
            'rows_imported' => 'integer',
            'rows_skipped' => 'integer',
            'attendance_confirmed_at' => 'datetime',
        ];
    }

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

    public function importer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(AudienceAttendance::class);
    }
}
