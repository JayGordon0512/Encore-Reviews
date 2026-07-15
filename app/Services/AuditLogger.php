<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditLogger
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function record(
        User $actor,
        string $action,
        Model $entity,
        ?string $organisationId,
        ?array $before,
        ?array $after,
        ?string $ipAddress,
        ?string $userAgent,
        string $correlationId
    ): AuditLog {
        return AuditLog::create([
            'organisation_id' => $organisationId,
            'user_id' => $actor->id,
            'action' => $action,
            'entity_type' => $entity->getMorphClass(),
            'entity_id' => (string) $entity->getKey(),
            'before_state' => $this->redact($before),
            'after_state' => $this->redact($after),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent === null ? null : Str::limit($userAgent, 1000, ''),
            'correlation_id' => $correlationId,
            'created_at' => now(),
        ]);
    }

    /**
     * @param  list<string>  $fields
     * @return array<string, mixed>
     */
    public function snapshot(Model $model, array $fields): array
    {
        return collect($fields)
            ->mapWithKeys(fn (string $field): array => [$field => $model->getAttribute($field)])
            ->all();
    }

    /**
     * @param  array<string, mixed>|null  $state
     * @return array<string, mixed>|null
     */
    private function redact(?array $state): ?array
    {
        if ($state === null) {
            return null;
        }

        $sensitiveTerms = ['password', 'secret', 'token', 'authorization', 'cookie'];

        return collect($state)
            ->reject(function (mixed $value, string $key) use ($sensitiveTerms): bool {
                $normalizedKey = Str::lower($key);

                return collect($sensitiveTerms)->contains(
                    fn (string $term): bool => Str::contains($normalizedKey, $term)
                );
            })
            ->all();
    }
}
