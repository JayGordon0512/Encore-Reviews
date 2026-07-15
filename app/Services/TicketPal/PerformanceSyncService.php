<?php

namespace App\Services\TicketPal;

use App\Models\Performance;
use App\Models\Show;
use App\Models\Venue;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PerformanceSyncService
{
    /**
     * @return array{performance: Performance, created: bool}
     */
    public function sync(array $data): array
    {
        $data['starts_at'] = CarbonImmutable::parse($data['starts_at'])->utc();

        if (array_key_exists('ends_at', $data) && $data['ends_at'] !== null) {
            $data['ends_at'] = CarbonImmutable::parse($data['ends_at'])->utc();
        }

        return DB::transaction(function () use ($data): array {
            $show = Show::query()
                ->with('organisation')
                ->where('provider_source', 'ticketpal')
                ->where('provider_event_id', $data['provider_event_id'])
                ->lockForUpdate()
                ->first();

            if (! $show) {
                throw ValidationException::withMessages([
                    'provider_event_id' => 'No matching TicketPal show was found.',
                ]);
            }

            if (! $show->organisation) {
                throw ValidationException::withMessages([
                    'provider_event_id' => 'The matching show is not assigned to an organisation.',
                ]);
            }

            $venue = $this->resolveVenue($show->organisation_id, $data);
            $performance = Performance::query()->firstOrCreate(
                [
                    'provider_source' => 'ticketpal',
                    'provider_performance_id' => $data['provider_performance_id'],
                ],
                [
                    'show_id' => $show->id,
                    'venue_id' => $venue->id,
                    'provider_event_id' => $data['provider_event_id'],
                    'starts_at' => $data['starts_at'],
                    'ends_at' => $data['ends_at'] ?? null,
                    'status' => $data['status'] ?? null,
                    'provider_updated_at' => now(),
                ]
            );

            $created = $performance->wasRecentlyCreated;

            if (! $created && $performance->show_id !== $show->id) {
                throw ValidationException::withMessages([
                    'provider_performance_id' => 'This TicketPal performance belongs to a different show.',
                ]);
            }

            if (! $created) {
                $updates = [
                    'starts_at' => $data['starts_at'],
                    'venue_id' => $venue->id,
                    'provider_updated_at' => now(),
                ];

                foreach (['ends_at', 'status'] as $field) {
                    if (array_key_exists($field, $data)) {
                        $updates[$field] = $data[$field];
                    }
                }

                $performance->update($updates);
            }

            return [
                'performance' => $performance->refresh(),
                'created' => $created,
            ];
        });
    }

    private function resolveVenue(string $organisationId, array $data): Venue
    {
        $slug = Str::slug($data['venue_name']);

        if ($slug === '') {
            $slug = 'venue-'.substr(hash('sha256', $data['venue_name']), 0, 12);
        }

        $venue = Venue::query()->firstOrCreate(
            [
                'organisation_id' => $organisationId,
                'slug' => $slug,
            ],
            [
                'name' => $data['venue_name'],
                'city' => $data['venue_city'] ?? null,
                'postcode' => $data['venue_postcode'] ?? null,
            ]
        );

        $updates = ['name' => $data['venue_name']];

        foreach (['venue_city' => 'city', 'venue_postcode' => 'postcode'] as $input => $column) {
            if (array_key_exists($input, $data)) {
                $updates[$column] = $data[$input];
            }
        }

        $venue->fill($updates);

        if ($venue->isDirty()) {
            $venue->save();
        }

        return $venue;
    }
}
