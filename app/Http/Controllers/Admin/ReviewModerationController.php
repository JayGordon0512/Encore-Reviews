<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ReviewModerationController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function update(Request $request, Review $review): RedirectResponse
    {
        Gate::authorize('moderate', $review);

        $validated = $request->validate([
            'moderation_status' => ['required', Rule::in(['approved', 'rejected'])],
            'moderation_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $before = $this->auditLogger->snapshot($review, ['moderation_status', 'moderation_reason']);

        DB::transaction(function () use ($request, $review, $validated, $before): void {
            $review->update([
                'moderation_status' => $validated['moderation_status'],
                'moderation_reason' => $validated['moderation_reason'] ?? null,
            ]);

            $this->auditLogger->record(
                $request->user(),
                'review.moderated',
                $review,
                $request->user()->organisation_id,
                $before,
                $this->auditLogger->snapshot($review, ['moderation_status', 'moderation_reason']),
                $request->ip(),
                $request->userAgent(),
                (string) Str::uuid()
            );
        });

        return back()->with('status', 'Review moderation updated.');
    }
}
