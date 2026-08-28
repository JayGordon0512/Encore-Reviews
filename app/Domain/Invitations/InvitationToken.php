<?php

namespace App\Domain\Invitations;

use Illuminate\Support\Str;
use RuntimeException;

final class InvitationToken
{
    public const VERSION = 2;

    public function create(): string
    {
        return 'eri_'.self::VERSION.'_'.Str::random(48);
    }

    public function digest(string $token): string
    {
        $key = config('encore.invitations.token_digest_key');
        if (! is_string($key) || $key === '') {
            throw new RuntimeException('Invitation token digest key is not configured.');
        }

        return hash_hmac('sha256', $token, $key);
    }

    /** @return list<string> */
    public function lookupDigests(string $token): array
    {
        $digests = [hash('sha256', $token)];
        $key = config('encore.invitations.token_digest_key');

        if (is_string($key) && $key !== '') {
            array_unshift($digests, hash_hmac('sha256', $token, $key));
        }
        foreach ((array) config('encore.invitations.previous_token_digest_keys', []) as $previousKey) {
            if (is_string($previousKey) && $previousKey !== '') {
                $digests[] = hash_hmac('sha256', $token, $previousKey);
            }
        }

        return array_values(array_unique($digests));
    }
}
