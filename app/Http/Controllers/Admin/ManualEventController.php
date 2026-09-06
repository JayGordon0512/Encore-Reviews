<?php

namespace App\Http\Controllers\Admin;

use App\Application\Events\CancelManualPerformanceService;
use App\Application\Events\StoredEventArtwork;
use App\Application\Events\StoreEventArtworkService;
use App\Application\Events\UpdateManualEventService;
use App\Http\Controllers\Controller;
use App\Models\Performance;
use App\Models\Show;
use App\Models\Venue;
use App\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class ManualEventController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly StoreEventArtworkService $artworkStorage,
    ) {}

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
            'event_image' => $this->artworkRules(),
            'ticket_url' => ['nullable', 'url:http,https', 'max:2000'],
            'venue_name' => ['nullable', 'string', 'max:255'],
            'venue_city' => ['nullable', 'string', 'max:255'],
            'venue_postcode' => ['nullable', 'string', 'max:30'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:1440'],
            'performances' => ['required', 'array', 'min:1', 'max:100'],
            'performances.*.starts_at' => ['required', 'date', 'distinct'],
        ]);

        $correlationId = (string) Str::uuid();
        $eventReference = (string) Str::uuid();
        $organisation = $request->user()->organisation;
        $storedArtwork = $request->hasFile('event_image')
            ? $this->artworkStorage->store($organisation->id, $request->file('event_image'))
            : null;

        try {
            $show = DB::transaction(function () use ($request, $validated, $organisation, $eventReference, $correlationId, $storedArtwork): Show {
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
                    'primary_image_path' => $storedArtwork?->url,
                    'primary_image_disk' => $storedArtwork?->disk,
                    'primary_image_storage_path' => $storedArtwork?->path,
                    'ticket_url' => $validated['ticket_url'] ?? null,
                    'ticket_url_source' => filled($validated['ticket_url'] ?? null) ? 'organiser' : null,
                    'provider_source' => Show::SOURCE_MANUAL,
                    'provider_event_id' => $eventReference,
                    'status' => 'upcoming',
                    'lifecycle_status' => 'active',
                ]);

                foreach ($validated['performances'] as $index => $performance) {
                    $startsAt = CarbonImmutable::parse($performance['starts_at']);
                    Performance::create([
                        'show_id' => $show->id,
                        'venue_id' => $venue?->id,
                        'starts_at' => $startsAt,
                        'ends_at' => $startsAt->addMinutes((int) $validated['duration_minutes']),
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
                        'duration_minutes' => (int) $validated['duration_minutes'],
                        'venue_id' => $venue?->id,
                        'has_custom_artwork' => $storedArtwork !== null,
                    ],
                    $request->ip(),
                    $request->userAgent(),
                    $correlationId,
                );

                return $show;
            });
        } catch (Throwable $exception) {
            $this->deleteArtwork($storedArtwork);

            throw $exception;
        }

        return redirect()->route('admin.events.show', $show)
            ->with('status', 'Event and '.count($validated['performances']).' date(s) created.');
    }

    public function show(Request $request, Show $show): View
    {
        $this->authorizeOwnedManualEvent($request, $show);
        $show->load([
            'performances' => fn ($query) => $query->with('venue')
                ->withCount([
                    'audienceAttendances',
                    'invitationSchedules as invitation_scheduled_count' => fn ($query) => $query->whereIn('review_invitation_schedules.status', ['scheduled', 'processing']),
                    'invitationSchedules as invitation_issued_count' => fn ($query) => $query->where('review_invitation_schedules.status', 'issued'),
                    'invitationSchedules as invitation_held_count' => fn ($query) => $query
                        ->where('review_invitation_schedules.status', 'suppressed')
                        ->where('review_invitation_schedules.suppression_reason', 'organiser_invitation_issuing_disabled'),
                    'invitationSchedules as invitation_attention_count' => fn ($query) => $query->where('review_invitation_schedules.status', 'dead_lettered'),
                    'invitationSchedules as invitation_stopped_count' => fn ($query) => $query
                        ->where(function ($query): void {
                            $query->where('review_invitation_schedules.status', 'cancelled')
                                ->orWhere(function ($query): void {
                                    $query->where('review_invitation_schedules.status', 'suppressed')
                                        ->where('review_invitation_schedules.suppression_reason', '!=', 'organiser_invitation_issuing_disabled');
                                });
                        }),
                ])
                ->withMin([
                    'invitationSchedules as next_invitation_at' => fn ($query) => $query->where('review_invitation_schedules.status', 'scheduled'),
                ], 'scheduled_for')
                ->orderBy('starts_at'),
            'audienceImports' => fn ($query) => $query->with('performance')->latest()->limit(10),
        ])->loadCount('audienceAttendances');

        return view('admin.events.show', [
            'show' => $show,
            'invitationIssuingEnabled' => (bool) config('encore.audience_imports.invitation_issuing_enabled'),
            'invitationDelayHours' => (int) config('encore.audience_imports.invitation_delay_hours'),
        ]);
    }

    public function edit(Request $request, Show $show): View
    {
        $this->authorizeOwnedManualEvent($request, $show);
        $show->load(['performances' => fn ($query) => $query->with('venue')->whereNotIn('status', ['cancelled', 'archived', 'deleted'])->orderBy('starts_at')]);
        $firstPerformance = $show->performances->first();
        $durationMinutes = $firstPerformance?->starts_at && $firstPerformance?->ends_at
            ? (int) $firstPerformance->starts_at->diffInMinutes($firstPerformance->ends_at)
            : (int) config('encore.invitations.default_event_duration_minutes');

        return view('admin.events.edit', compact('show', 'durationMinutes'));
    }

    public function update(Request $request, Show $show, UpdateManualEventService $updater): RedirectResponse
    {
        $this->authorizeOwnedManualEvent($request, $show);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:10000'],
            'genre' => ['nullable', 'string', 'max:100'],
            'ticket_url' => ['nullable', 'url:http,https', 'max:2000'],
            'venue_name' => ['nullable', 'string', 'max:255'],
            'venue_city' => ['nullable', 'string', 'max:255'],
            'venue_postcode' => ['nullable', 'string', 'max:30'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:1440'],
            'performances' => ['required', 'array', 'min:1', 'max:100'],
            'performances.*.id' => ['nullable', 'uuid', 'distinct'],
            'performances.*.starts_at' => ['required', 'date', 'distinct'],
        ]);
        $rescheduled = $updater->update(
            $request->user(),
            $show,
            $validated,
            $request->ip(),
            $request->userAgent(),
        );

        return redirect()->route('admin.events.show', $show)
            ->with('status', "Event updated; {$rescheduled} unsent invitation schedule(s) recalculated.");
    }

    public function cancelPerformance(
        Request $request,
        Show $show,
        Performance $performance,
        CancelManualPerformanceService $canceller,
    ): RedirectResponse {
        $this->authorizeOwnedManualEvent($request, $show);
        abort_unless($performance->show_id === $show->id, 404);
        $canceller->cancel($request->user(), $show, $performance, $request->ip(), $request->userAgent());

        return redirect()->route('admin.events.show', $show)
            ->with('status', 'Performance cancelled and its unused invitations withdrawn.');
    }

    public function updateArtwork(Request $request, Show $show): RedirectResponse
    {
        $this->authorizeOwnedManualEvent($request, $show);
        $request->validate(['event_image' => $this->artworkRules(required: true)]);

        $storedArtwork = $this->artworkStorage->store($show->organisation_id, $request->file('event_image'));
        $previousDisk = $show->primary_image_disk;
        $previousPath = $show->primary_image_storage_path;
        $correlationId = (string) Str::uuid();

        try {
            DB::transaction(function () use ($request, $show, $storedArtwork, $correlationId): void {
                $show->update([
                    'primary_image_path' => $storedArtwork->url,
                    'primary_image_disk' => $storedArtwork->disk,
                    'primary_image_storage_path' => $storedArtwork->path,
                ]);

                $this->auditLogger->record(
                    $request->user(),
                    'event.manual_artwork_updated',
                    $show,
                    $show->organisation_id,
                    null,
                    ['has_custom_artwork' => true],
                    $request->ip(),
                    $request->userAgent(),
                    $correlationId,
                );
            });
        } catch (Throwable $exception) {
            $this->deleteArtwork($storedArtwork);

            throw $exception;
        }

        $this->artworkStorage->delete($previousDisk, $previousPath);

        return back()->with('status', 'Event artwork updated.');
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

    /** @return list<string> */
    private function artworkRules(bool $required = false): array
    {
        return [
            $required ? 'required' : 'nullable',
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:'.config('encore.event_images.max_size_kb', 5120),
            'dimensions:min_width=600,min_height=400,max_width=6000,max_height=6000',
        ];
    }

    private function deleteArtwork(?StoredEventArtwork $artwork): void
    {
        if ($artwork !== null) {
            $this->artworkStorage->delete($artwork->disk, $artwork->path);
        }
    }
}
