<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class OrganisationUserMembership extends Pivot
{
    use HasUuids;

    protected $table = 'organisation_user_memberships';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'organisation_id',
        'user_id',
        'role',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
