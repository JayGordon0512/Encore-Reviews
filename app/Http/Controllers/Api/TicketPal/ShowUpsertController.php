<?php

namespace App\Http\Controllers\Api\TicketPal;

use App\Http\Controllers\Controller;
use App\Models\Show;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShowUpsertController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider_event_id' => ['required', 'string'],
            'title' => ['required', 'string'],
            'ticket_url' => ['required', 'url'],
            'slug' => ['sometimes', 'string'],
            'summary' => ['sometimes', 'nullable', 'string'],
            'description' => ['sometimes', 'nullable', 'string'],
            'genre' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'nullable', 'in:upcoming,now_playing,archived'],
            'primary_image_path' => ['sometimes', 'nullable', 'string'],
            'ticket_url_source' => ['sometimes', 'nullable', 'string'],
        ]);

        $providerSource = 'ticketpal';
        $providerEventId = $validated['provider_event_id'];
        $ticketUrlSource = $validated['ticket_url_source'] ?? 'ticketpal';

        [$show, $created] = DB::transaction(function () use ($validated, $providerSource, $providerEventId, $ticketUrlSource): array {
            $show = Show::query()
                ->where('provider_source', $providerSource)
                ->where('provider_event_id', $providerEventId)
                ->lockForUpdate()
                ->first();

            $mutable = [
                'title' => $validated['title'],
                'ticket_url' => $validated['ticket_url'],
                'ticket_url_source' => $ticketUrlSource,
                'ticket_url_last_synced_at' => now(),
            ];

            foreach (['summary', 'description', 'genre', 'status', 'primary_image_path'] as $field) {
                if (array_key_exists($field, $validated)) {
                    $mutable[$field] = $validated[$field];
                }
            }

            if ($show !== null) {
                if (($show->slug === null || $show->slug === '') && array_key_exists('slug', $validated)) {
                    $show->slug = $this->generateUniqueSlug($validated['slug']);
                }

                $show->fill($mutable);
                $show->save();

                return [$show->refresh(), false];
            }

            $createData = $mutable + [
                'provider_source' => $providerSource,
                'provider_event_id' => $providerEventId,
                'slug' => $this->generateUniqueSlug($validated['slug'] ?? $validated['title']),
                'status' => $mutable['status'] ?? 'upcoming',
            ];

            $show = Show::create($createData);

            return [$show, true];
        });

        return response()->json([
            'ok' => true,
            'show' => [
                'id' => $show->id,
                'slug' => $show->slug,
                'title' => $show->title,
                'ticket_url' => $show->ticket_url,
                'provider_source' => $show->provider_source,
                'provider_event_id' => $show->provider_event_id,
                'updated_at' => $show->updated_at,
            ],
            'created' => $created,
        ]);
    }

    private function generateUniqueSlug(string $value): string
    {
        $base = Str::slug($value);
        $slug = $base !== '' ? $base : Str::random(8);
        $candidate = $slug;
        $suffix = 2;

        while (Show::query()->where('slug', $candidate)->exists()) {
            $candidate = $slug.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
