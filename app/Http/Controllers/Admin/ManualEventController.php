<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Performance;
use App\Models\Show;
use App\Models\Venue;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ManualEventController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function create(): View
    {
        Gate::authorize('createManual', Show::class);

        return view('admin.events.create');
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('createManual', Show::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:10000'],
            'genre' => ['nullable', 'string', 'max:100'],
            'ticket_url' => ['nullable', 'url:http,https', 'max:2000'],
            'venue_name' => ['nullable', 'string', 'max:255'],
            'venue_city' => ['nullable', 'string', 'max:255'],
            'venue_postcode' => ['nullable', 'string', 'max:30'],
            'performances' => ['required', 'array', 'min:1', 'max:100'],
            'performances.*.starts_at' => ['required', 'date', 'distinct'],
            'performances.*.ends_at' => ['nullable', 'date', 'after:performances.*.starts_at'],
        ]);

        $correlationId = (string) Str::uuid();
        $eventReference = (string) Str::uuid();
        $organisation = $request->user()->organisation;

        $show = DB::transaction(function () use ($request, $validated, $organisation, $eventReference, $correlationId): Show {
            $venue = null;
            if (filled($validated['venue_name'] ?? null)) {
                $venue = Venue::create([
                    'organisation_id' => $organisation->id,
                    'name' => trim($validated['venue_name']),
                    'slug' => $this->uniqueVenueSlug($validated['venue_name']),
                    'city' => filled($validated['venue_city'] ?? null) ? trim($validated['venue_city']) : null,
                    'postcode' => filled($validated['venue_postcode'] ?? null) ? trim($validated['venue_postcode']) : null,
                ]);
            }

            $show = Show::create([
                'organisation_id' => $organisation->id,
                'title' => trim($validated['title']),
                'slug' => $this->uniqueShowSlug($validated['title']),
                'summary' => filled($validated['summary'] ?? null) ? trim($validated['summary']) : null,
                'description' => filled($validated['description'] ?? null) ? trim($validated['description']) : null,
                'genre' => filled($validated['genre'] ?? null) ? trim($validated['genre']) : null,
                'ticket_url' => $validated['ticket_url'] ?? null,
                'ticket_url_source' => filled($validated['ticket_url'] ?? null) ? 'organiser' : null,
                'provider_source' => Show::SOURCE_MANUAL,
                'provider_event_id' => $eventReference,
                'status' => 'upcoming',
                'lifecycle_status' => 'active',
            ]);

            foreach ($validated['performances'] as $index => $performance) {
                Performance::create([
                    'show_id' => $show->id,
                    'venue_id' => $venue?->id,
                    'starts_at' => $performance['starts_at'],
                    'ends_at' => $performance['ends_at'] ?? null,
                    'status' => 'scheduled',
                    'provider_source' => Show::SOURCE_MANUAL,
                    'provider_event_id' => $eventReference,
                    'provider_performance_id' => $eventReference.'-'.($index + 1),
                ]);
            }

            $this->auditLogger->record(
                $request->user(),
                'event.manual_created',
                $show,
                $organisation->id,
                null,
                [
                    'title' => $show->title,
                    'source' => Show::SOURCE_MANUAL,
                    'performance_count' => count($validated['performances']),
                    'venue_id' => $venue?->id,
                ],
                $request->ip(),
                $request->userAgent(),
                $correlationId,
            );

            return $show;
        });

        return redirect()->route('admin.events.show', $show)
            ->with('status', 'Event and '.count($validated['performances']).' date(s) created.');
    }

    public function show(Request $request, Show $show): View
    {
        $this->authorizeOwnedManualEvent($request, $show);
        $show->load([
            'performances' => fn ($query) => $query->with('venue')->withCount('audienceAttendances')->orderBy('starts_at'),
            'audienceImports' => fn ($query) => $query->with('performance')->latest()->limit(10),
        ])->loadCount('audienceAttendances');

        return view('admin.events.show', compact('show'));
    }

    public static function authorizeOwnedManualEvent(Request $request, Show $show): void
    {
        abort_unless($request->user()->organisation_id === $show->organisation_id, 404);
        Gate::authorize('manageManual', $show);
    }

    private function uniqueShowSlug(string $title): string
    {
        return $this->uniqueSlug(Show::query(), $title, 'event');
    }

    private function uniqueVenueSlug(string $name): string
    {
        return $this->uniqueSlug(Venue::query(), $name, 'venue');
    }

    private function uniqueSlug(Builder $query, string $value, string $fallback): string
    {
        $base = Str::slug($value) ?: $fallback;
        $slug = $base;
        $suffix = 2;
        while ((clone $query)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
