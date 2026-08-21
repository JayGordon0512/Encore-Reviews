<?php

namespace App\Application\Catalogue;

use App\Domain\Integration\ProviderAuthority;
use App\Models\IntegrationIdempotencyRecord;
use App\Models\IntegrationOrganisationMapping;
use App\Models\IntegrationPerformanceMapping;
use App\Models\IntegrationShowMapping;
use App\Models\IntegrationUserMapping;
use App\Models\Organisation;
use App\Models\OrganisationUserMembership;
use App\Models\Performance;
use App\Models\Show;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

final class ProviderV2CatalogueImportService
{
    /** @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function upsertOrganisation(ProviderAuthority $authority, array $payload, string $key, string $digest, string $correlationId): array
    {
        return $this->idempotent($authority, 'catalogue-organisation:write', $key, $digest, $correlationId,
            function () use ($authority, $payload, $correlationId): array {
                $mapping = $this->organisationMapping($authority, $payload['provider_organisation_id']);
                $created = $mapping === null;
                $organisation = $mapping?->organisation;

                if (! $organisation) {
                    $organisation = Organisation::create([
                        'name' => $payload['name'],
                        'is_active' => $payload['status'] === 'active',
                        'lifecycle_status' => $payload['status'],
                    ]);
                    $mapping = IntegrationOrganisationMapping::create([
                        'provider_id' => $authority->credential->provider_id,
                        'account_reference' => $authority->accountReference,
                        'external_organisation_id' => $payload['provider_organisation_id'],
                        'organisation_id' => $organisation->id,
                    ]);
                } else {
                    $organisation->update([
                        'name' => $payload['name'],
                        'is_active' => $payload['status'] === 'active',
                        'lifecycle_status' => $payload['status'],
                    ]);
                }

                $authority->credential->organisations()->syncWithoutDetaching([
                    $organisation->id => ['created_at' => now()],
                ]);

                return $this->accepted('organisation', $created, $mapping->id, $correlationId, [
                    'provider_organisation_id' => $payload['provider_organisation_id'],
                    'organisation_id' => $organisation->id,
                ]);
            });
    }

    /** @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function upsertMembership(ProviderAuthority $authority, array $payload, string $key, string $digest, string $correlationId): array
    {
        return $this->idempotent($authority, 'catalogue-membership:write', $key, $digest, $correlationId,
            function () use ($authority, $payload, $correlationId): array {
                $organisationMapping = $this->requiredOrganisationMapping($authority, $payload['provider_organisation_id']);
                $organisation = $organisationMapping->organisation;
                $this->assertCredentialOrganisation($authority, $organisation->id);

                $email = Str::lower(trim($payload['email']));
                $userMapping = IntegrationUserMapping::query()
                    ->with('user')
                    ->where('provider_id', $authority->credential->provider_id)
                    ->where('account_reference', $authority->accountReference)
                    ->where('external_user_id', $payload['provider_user_id'])
                    ->lockForUpdate()
                    ->first();
                $user = $userMapping?->user;

                if ($user && $user->email !== $email && User::query()->where('email', $email)->whereKeyNot($user->id)->exists()) {
                    throw new ProviderCatalogueImportException('mapping_conflict', 'The supplied email belongs to a different Encore user.');
                }

                if (! $user) {
                    $user = User::query()->where('email', $email)->lockForUpdate()->first();
                    if ($user?->isSuperAdmin()) {
                        throw new ProviderCatalogueImportException('mapping_conflict', 'An Encore super administrator cannot be claimed by a provider membership.');
                    }
                    if (! $user) {
                        $user = User::create([
                            'name' => $payload['name'],
                            'email' => $email,
                            'password' => Hash::make(Str::random(64)),
                            'organisation_id' => $organisation->id,
                            'role' => 'customer_admin',
                            'is_active' => $payload['status'] === 'active',
                        ]);
                    }
                    $userMapping = IntegrationUserMapping::create([
                        'provider_id' => $authority->credential->provider_id,
                        'account_reference' => $authority->accountReference,
                        'external_user_id' => $payload['provider_user_id'],
                        'user_id' => $user->id,
                    ]);
                }

                if ($user->organisation_id === null) {
                    $user->organisation_id = $organisation->id;
                }
                $user->name = $payload['name'];
                $user->email = $email;
                $user->role = 'customer_admin';
                if ($payload['status'] === 'active') {
                    $user->is_active = true;
                }
                $user->save();

                $membership = OrganisationUserMembership::query()
                    ->where('organisation_id', $organisation->id)
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->first();
                $created = $membership === null;
                $membership ??= new OrganisationUserMembership([
                    'organisation_id' => $organisation->id,
                    'user_id' => $user->id,
                ]);
                $membership->fill([
                    'role' => $payload['role'],
                    'is_active' => $payload['status'] === 'active',
                ])->save();

                if ($payload['status'] !== 'active' && ! OrganisationUserMembership::query()
                    ->where('user_id', $user->id)->where('is_active', true)->exists()) {
                    $user->update(['is_active' => false]);
                }

                return $this->accepted('membership', $created, $membership->id, $correlationId, [
                    'provider_organisation_id' => $payload['provider_organisation_id'],
                    'provider_user_id' => $payload['provider_user_id'],
                    'organisation_id' => $organisation->id,
                    'user_id' => $user->id,
                    'membership_id' => $membership->id,
                ]);
            });
    }

    /** @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function upsertShow(ProviderAuthority $authority, array $payload, string $key, string $digest, string $correlationId): array
    {
        return $this->idempotent($authority, 'catalogue-show:write', $key, $digest, $correlationId,
            function () use ($authority, $payload, $correlationId): array {
                $organisationMapping = $this->requiredOrganisationMapping($authority, $payload['provider_organisation_id']);
                $this->assertCredentialOrganisation($authority, $organisationMapping->organisation_id);
                $mapping = IntegrationShowMapping::query()
                    ->with('show')
                    ->where('provider_id', $authority->credential->provider_id)
                    ->where('account_reference', $authority->accountReference)
                    ->where('external_show_id', $payload['provider_show_id'])
                    ->lockForUpdate()
                    ->first();

                if ($mapping && $mapping->organisation_mapping_id !== $organisationMapping->id) {
                    throw new ProviderCatalogueImportException('mapping_conflict', 'The provider show is already mapped to another organisation.');
                }

                $created = $mapping === null;
                $show = $mapping?->show;
                $publicStatus = $this->publicShowStatus($payload['status']);
                $attributes = [
                    'organisation_id' => $organisationMapping->organisation_id,
                    'title' => $payload['title'],
                    'description' => $payload['description'] ?? null,
                    'genre' => $payload['category'] ?? null,
                    'primary_image_path' => $payload['image_url'] ?? null,
                    'status' => $publicStatus,
                    'lifecycle_status' => $payload['status'],
                    'reviews_locked' => in_array($payload['status'], ['ended', 'cancelled', 'archived', 'deleted'], true),
                    'ticket_url' => $payload['public_url'],
                    'ticket_url_source' => $authority->providerSlug,
                    'ticket_url_last_synced_at' => now(),
                ];

                if (! $show) {
                    $show = Show::create($attributes + [
                        'slug' => $this->uniqueShowSlug($payload['title']),
                        'provider_source' => $authority->providerSlug,
                        'provider_event_id' => $payload['provider_show_id'],
                    ]);
                    $mapping = IntegrationShowMapping::create([
                        'organisation_mapping_id' => $organisationMapping->id,
                        'provider_id' => $authority->credential->provider_id,
                        'account_reference' => $authority->accountReference,
                        'external_show_id' => $payload['provider_show_id'],
                        'organisation_id' => $organisationMapping->organisation_id,
                        'show_id' => $show->id,
                    ]);
                } else {
                    $show->update($attributes);
                }

                return $this->accepted('show', $created, $mapping->id, $correlationId, [
                    'provider_organisation_id' => $payload['provider_organisation_id'],
                    'provider_show_id' => $payload['provider_show_id'],
                    'organisation_id' => $organisationMapping->organisation_id,
                    'show_id' => $show->id,
                ]);
            });
    }

    /** @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function upsertPerformance(ProviderAuthority $authority, array $payload, string $key, string $digest, string $correlationId): array
    {
        return $this->idempotent($authority, 'catalogue-performance:write', $key, $digest, $correlationId,
            function () use ($authority, $payload, $correlationId): array {
                $showMapping = IntegrationShowMapping::query()
                    ->with('show')
                    ->where('provider_id', $authority->credential->provider_id)
                    ->where('account_reference', $authority->accountReference)
                    ->where('external_show_id', $payload['provider_show_id'])
                    ->lockForUpdate()
                    ->first();
                if (! $showMapping) {
                    throw new ProviderCatalogueImportException('mapping_not_found', 'The provider show mapping could not be resolved.');
                }
                $this->assertCredentialOrganisation($authority, $showMapping->organisation_id);

                $mapping = IntegrationPerformanceMapping::query()
                    ->with('performance')
                    ->where('provider_id', $authority->credential->provider_id)
                    ->where('account_reference', $authority->accountReference)
                    ->where('external_performance_id', $payload['provider_performance_id'])
                    ->lockForUpdate()
                    ->first();
                if ($mapping && $mapping->show_mapping_id !== $showMapping->id) {
                    throw new ProviderCatalogueImportException('mapping_conflict', 'The provider performance is already mapped to another show.');
                }

                $location = $payload['location'];
                $venue = $this->resolveVenue($showMapping->organisation_id, $location);
                $created = $mapping === null;
                $performance = $mapping?->performance;
                $attributes = [
                    'show_id' => $showMapping->show_id,
                    'venue_id' => $venue->id,
                    'starts_at' => $payload['starts_at'],
                    'ends_at' => $payload['ends_at'] ?? null,
                    'status' => $payload['status'],
                    'provider_updated_at' => now(),
                ];

                if (! $performance) {
                    $performance = Performance::create($attributes + [
                        'provider_source' => $authority->providerSlug,
                        'provider_event_id' => $payload['provider_show_id'],
                        'provider_performance_id' => $payload['provider_performance_id'],
                    ]);
                    $mapping = IntegrationPerformanceMapping::create([
                        'show_mapping_id' => $showMapping->id,
                        'provider_id' => $authority->credential->provider_id,
                        'account_reference' => $authority->accountReference,
                        'external_performance_id' => $payload['provider_performance_id'],
                        'organisation_id' => $showMapping->organisation_id,
                        'show_id' => $showMapping->show_id,
                        'performance_id' => $performance->id,
                    ]);
                } else {
                    $performance->update($attributes);
                }

                return $this->accepted('performance', $created, $mapping->id, $correlationId, [
                    'provider_show_id' => $payload['provider_show_id'],
                    'provider_performance_id' => $payload['provider_performance_id'],
                    'organisation_id' => $showMapping->organisation_id,
                    'show_id' => $showMapping->show_id,
                    'performance_id' => $performance->id,
                    'venue_id' => $venue->id,
                ]);
            });
    }

    /** @param callable(): array<string, mixed> $operation
     * @return array<string, mixed>
     */
    private function idempotent(ProviderAuthority $authority, string $operationName, string $key, string $digest, string $correlationId, callable $operation): array
    {
        try {
            return DB::transaction(function () use ($authority, $operationName, $key, $digest, $correlationId, $operation): array {
                $existing = $this->idempotencyRecord($authority, $operationName, $key);
                if ($existing) {
                    return $this->duplicateOutcome($authority, $existing, $digest, $correlationId);
                }

                $record = $this->reserveIdempotency($authority, $operationName, $key, $digest, $correlationId);
                if (is_array($record)) {
                    return $record;
                }
                $result = $operation();
                $record->forceFill([
                    'status' => 'completed',
                    'outcome_type' => $result['resource_type'],
                    'outcome_id' => $result['_outcome_id'],
                    'response_status' => 202,
                ])->save();
                unset($result['_outcome_id']);

                return $result;
            });
        } catch (ProviderCatalogueImportException $exception) {
            return ['error' => $exception->errorCode, 'message' => $exception->getMessage()];
        }
    }

    private function idempotencyRecord(ProviderAuthority $authority, string $operation, string $key): ?IntegrationIdempotencyRecord
    {
        return IntegrationIdempotencyRecord::query()
            ->where('credential_id', $authority->credential->id)
            ->where('operation', $operation)
            ->where('idempotency_key', $key)
            ->lockForUpdate()
            ->first();
    }

    /** @return IntegrationIdempotencyRecord|array<string, mixed> */
    private function reserveIdempotency(ProviderAuthority $authority, string $operation, string $key, string $digest, string $correlationId): IntegrationIdempotencyRecord|array
    {
        $id = (string) Str::uuid();
        $inserted = IntegrationIdempotencyRecord::query()->insertOrIgnore([
            'id' => $id,
            'credential_id' => $authority->credential->id,
            'operation' => $operation,
            'idempotency_key' => $key,
            'request_digest' => $digest,
            'status' => 'processing',
            'first_correlation_id' => $correlationId,
            'last_correlation_id' => $correlationId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        if ($inserted === 1) {
            return IntegrationIdempotencyRecord::findOrFail($id);
        }
        $existing = $this->idempotencyRecord($authority, $operation, $key);
        if (! $existing) {
            throw new RuntimeException('Idempotency reservation could not be resolved.');
        }

        return $this->duplicateOutcome($authority, $existing, $digest, $correlationId);
    }

    /** @return array<string, mixed> */
    private function duplicateOutcome(ProviderAuthority $authority, IntegrationIdempotencyRecord $record, string $digest, string $correlationId): array
    {
        if (! hash_equals($record->request_digest, $digest)) {
            return ['error' => 'idempotency_conflict'];
        }
        $record->forceFill(['last_correlation_id' => $correlationId])->save();

        $mapping = match ($record->outcome_type) {
            'organisation' => $this->organisationOutcome($authority, $record->outcome_id),
            'membership' => $this->membershipOutcome($authority, $record->outcome_id),
            'show' => $this->showOutcome($authority, $record->outcome_id),
            'performance' => $this->performanceOutcome($authority, $record->outcome_id),
            default => throw new RuntimeException('The idempotent catalogue outcome is unavailable.'),
        };

        return [
            'status' => 'duplicate',
            'resource_type' => $record->outcome_type,
            'action' => 'unchanged',
            'mapping' => $mapping,
            'correlation_id' => $correlationId,
        ];
    }

    /** @param array<string, mixed> $mapping
     * @return array<string, mixed>
     */
    private function accepted(string $type, bool $created, string $outcomeId, string $correlationId, array $mapping): array
    {
        return [
            'status' => 'accepted',
            'resource_type' => $type,
            'action' => $created ? 'created' : 'updated',
            'mapping' => $mapping,
            'correlation_id' => $correlationId,
            '_outcome_id' => $outcomeId,
        ];
    }

    private function organisationMapping(ProviderAuthority $authority, string $externalId): ?IntegrationOrganisationMapping
    {
        return IntegrationOrganisationMapping::query()
            ->with('organisation')
            ->where('provider_id', $authority->credential->provider_id)
            ->where('account_reference', $authority->accountReference)
            ->where('external_organisation_id', $externalId)
            ->lockForUpdate()
            ->first();
    }

    private function requiredOrganisationMapping(ProviderAuthority $authority, string $externalId): IntegrationOrganisationMapping
    {
        return $this->organisationMapping($authority, $externalId)
            ?? throw new ProviderCatalogueImportException('mapping_not_found', 'The provider organisation mapping could not be resolved.');
    }

    private function assertCredentialOrganisation(ProviderAuthority $authority, string $organisationId): void
    {
        if (! $authority->credential->organisations()->whereKey($organisationId)->exists()) {
            throw new ProviderCatalogueImportException('mapping_not_found', 'The provider credential is not scoped to the mapped organisation.');
        }
    }

    /** @param array<string, mixed> $location */
    private function resolveVenue(string $organisationId, array $location): Venue
    {
        $base = Str::slug($location['name']);
        $slug = $base !== '' ? $base : 'location-'.substr(hash('sha256', $location['name']), 0, 12);
        $venue = Venue::query()->firstOrNew(['organisation_id' => $organisationId, 'slug' => $slug]);
        $venue->fill([
            'name' => $location['name'],
            'city' => $location['city'] ?? null,
            'postcode' => $location['postcode'] ?? null,
            'country' => $location['country'] ?? null,
            'website_url' => $location['public_url'] ?? null,
        ])->save();

        return $venue;
    }

    private function uniqueShowSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'show';
        $candidate = $base;
        $suffix = 2;
        while (Show::query()->where('slug', $candidate)->exists()) {
            $candidate = $base.'-'.$suffix++;
        }

        return $candidate;
    }

    private function publicShowStatus(string $status): string
    {
        return match ($status) {
            'now_playing' => 'now_playing',
            'ended', 'cancelled', 'archived', 'deleted' => 'archived',
            default => 'upcoming',
        };
    }

    /** @return array<string, mixed> */
    private function organisationOutcome(ProviderAuthority $authority, string $mappingId): array
    {
        $mapping = IntegrationOrganisationMapping::query()->whereKey($mappingId)->firstOrFail();

        return ['provider_organisation_id' => $mapping->external_organisation_id, 'organisation_id' => $mapping->organisation_id];
    }

    /** @return array<string, mixed> */
    private function membershipOutcome(ProviderAuthority $authority, string $membershipId): array
    {
        $membership = OrganisationUserMembership::query()->findOrFail($membershipId);
        $organisationMapping = IntegrationOrganisationMapping::query()
            ->where('provider_id', $authority->credential->provider_id)
            ->where('account_reference', $authority->accountReference)
            ->where('organisation_id', $membership->organisation_id)->firstOrFail();
        $userMapping = IntegrationUserMapping::query()
            ->where('provider_id', $authority->credential->provider_id)
            ->where('account_reference', $authority->accountReference)
            ->where('user_id', $membership->user_id)->firstOrFail();

        return [
            'provider_organisation_id' => $organisationMapping->external_organisation_id,
            'provider_user_id' => $userMapping->external_user_id,
            'organisation_id' => $membership->organisation_id,
            'user_id' => $membership->user_id,
            'membership_id' => $membership->id,
        ];
    }

    /** @return array<string, mixed> */
    private function showOutcome(ProviderAuthority $authority, string $mappingId): array
    {
        $mapping = IntegrationShowMapping::query()->with('organisationMapping')->findOrFail($mappingId);

        return [
            'provider_organisation_id' => $mapping->organisationMapping->external_organisation_id,
            'provider_show_id' => $mapping->external_show_id,
            'organisation_id' => $mapping->organisation_id,
            'show_id' => $mapping->show_id,
        ];
    }

    /** @return array<string, mixed> */
    private function performanceOutcome(ProviderAuthority $authority, string $mappingId): array
    {
        $mapping = IntegrationPerformanceMapping::query()->with('performance')->findOrFail($mappingId);
        $showMapping = IntegrationShowMapping::query()->findOrFail($mapping->show_mapping_id);

        return [
            'provider_show_id' => $showMapping->external_show_id,
            'provider_performance_id' => $mapping->external_performance_id,
            'organisation_id' => $mapping->organisation_id,
            'show_id' => $mapping->show_id,
            'performance_id' => $mapping->performance_id,
            'venue_id' => $mapping->performance->venue_id,
        ];
    }
}
