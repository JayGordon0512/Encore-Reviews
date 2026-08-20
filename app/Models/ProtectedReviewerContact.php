<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ProtectedReviewerContact extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'email_ciphertext', 'display_name_ciphertext', 'email_fingerprint',
        'fingerprint_version', 'status',
    ];

    protected $hidden = [
        'email_ciphertext', 'display_name_ciphertext', 'email_fingerprint',
    ];

    protected function casts(): array
    {
        return ['fingerprint_version' => 'integer'];
    }
}
